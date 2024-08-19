<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\CalcType;
use App\Entity\Calculated;
use App\Entity\Savedcontact;
use App\Form\CalcTypeTest;
use App\Service\OfferService;
use App\Form\CobuyersFormType;
use App\Repository\BankMainRepository;
use App\Repository\BankRepository;
use App\Service\Calculator\CalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CalculatorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    /**
     * @Route("/calc", name="calc_index")
     * Основная страница калькулятора нов
     */
    public function calc_index_new(Request $request, CalculatorService $calculatorService): Response
    {
        if(!$this->isGranted('isUserEmailConfirmed')) return $this->redirectToRoute('email_not_active');

        $newOffer = new Offer();
        $form = $this->createForm(CalcType::class, $newOffer);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * Список банков для вывода в шаблоне получаем из сервиса
             * Сервис возвращает массив сущностей BankMain
             */
            $resultBanks = $calculatorService->getBanksResult($newOffer);
        }
//        dump($resultBanks ?? []);
        //dump($newOffer);
        return $this->render('calculator/index_new.html.twig', [
            'calcForm' => $form->createView(),
            'bankList' => $resultBanks ?? [],
            'offer' => $newOffer,
        ]);
    }


    /**
     * @Route("/offer/create_new", name="offer_create_new")
     * Создается черновик заявки и подзаявок. Со статусом 0
     */
    public function createOfferNew(
        Request $request,
        CalculatorService $calculatorService,
        BankMainRepository $bankMainRepository,
        BankRepository $bankRepository,
        EntityManagerInterface $em,
    )
    {
        $newOffer = new Offer();
        $form = $this->createForm(CalcType::class, $newOffer, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newOffer->time = intval($form->get('time')->getData());
            $newOffer->cost = intval($form->get('cost')->getData());
            $newOffer->firstpay = intval($form->get('firstpay')->getData());
            $newOffer->motherCapSize = intval($form->get('motherCapSize')->getData());
            $newOffer->addAmount = intval($form->get('addAmount')->getData());
            $newOffer->withAddAmount = intval($form->get('withAddAmount')->getData());
            $newOffer->withConsolidation = intval($form->get('withConsolidation')->getData());
            $newOffer->creditsCount = intval($form->get('creditsCount')->getData());

            /**
             * Банки приходят в поле "banks" формы в виде json формата
             * например [{8: "Открытие"}, {4, "Сбербанк"}]
             */
            $bankList = json_decode($form->get('banks')->getData(), true);
            $bankDepricated = $bankRepository->findAll()[0]; //TODO удалить потом при смене банков
            $newOffer->setUser($this->getUser());
            $em->persist($newOffer);

            foreach($bankList as $key => $chosenBank) {
                $bankMain = $bankMainRepository->find($key);
                if(!$bankMain) throw new \Exception('Банк не найден');

                $bankMain = $calculatorService->testBank($bankMain, $newOffer);
                if(!$bankMain->isAccept) throw new \Exception('Банк не подходит для заявки');
                $calcEntity = new Calculated();
                if($newOffer->getIsMotherCap()){
                    $calcEntity->setMotherCapSize($newOffer->motherCapSize);
                }
                $calcEntity->setOffer($newOffer);
                $calcEntity->setMonthcount($newOffer->time);
                $calcEntity->setFirstpay($newOffer->firstpay);
                if (empty($chosenBank['dinamic'])){
                    $calcEntity->setFullsumm($bankMain->calcData['bodySumm']);
                    $calcEntity->setProcent($bankMain->calcData['rate']);
                    $calcEntity->setMounthpay($bankMain->calcData['monthlyPayment']);
                } else {
                    $calcEntity->setFullsumm($chosenBank['dinamic']['bodySumm']);
                    $calcEntity->setProcent($chosenBank['dinamic']['rate']);
                    $calcEntity->setMounthpay($chosenBank['dinamic']['monthlyPayment']);
                }

                $other = [
                    'bankId' => $bankMain->getId(),
                    'bankName' => $bankMain->getTitle(),
                    'version' => 2,
                    'dinamic' => $chosenBank['dinamic'],
                    'withAddAmount' => $newOffer->withAddAmount,
                    'withConsolidation' => $newOffer->withConsolidation,
                    'addAmount' => $newOffer->addAmount,
                    'creditsCount' => $newOffer->creditsCount,
                ];
                $calcEntity->setOther($other);
                $calcEntity->setBank($bankDepricated);
                $em->persist($calcEntity);
            }
            $em->flush();
            $this->addFlash(
                'info',
                'Черновик заявки создан. Заполните данные о заемщике, чтобы сохранить ее'
            );
            return $this->redirectToRoute('offer_success', ["id" => $newOffer->getId()]);
        }
        dd($form);
    }


    /**
     * @Route("/offer/success/{id}", name="offer_success")
     */
    public function successOffer(
        Offer $offer,
        Request $request,
        EntityManagerInterface $em,
    ): Response
    {
        if(!$this->isGranted('edit', $offer)){
            $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }

        $cobuyersForm = $this->createForm(CobuyersFormType::class, $offer, [
            'passport_mask' => $offer->getNationality() === 10,
        ]);

        $cobuyersForm->handleRequest($request);
        if ($cobuyersForm->isSubmitted() && $cobuyersForm->isValid()) {

            //Устанавливаем автора у заёмщика
            $buyer = $offer->getBuyer();
            $buyer->setCreator($this->getUser());

            $offer_data = $offer->getOther();
            $offer_data['is2docWorkValid'] = false;
            $offer->setOther($offer_data);

            $other = $buyer->getOther();
            $other['fio'] = $buyer->getFio();
            $buyer->setOther($other);

            //Копирование всех данных займщика и созаемщиков в сохраненный контакт
            if($cobuyersForm->get('buyer')->get('isSave')->getData()){
                $this->createSavedContact($buyer);
            }
            foreach ($offer->getCobuyers() as $key => $cobuyer) {
                $cobuyer->setOther([]);
                $cobuyer->setCreator($this->getUser());
                $cobOther = $cobuyer->getOther();
                $cobOther['fio'] = $cobuyer->getFio();
                $cobuyer->setOther($cobOther);
                if($cobuyersForm->get('cobuyers')[$key]->get('isSave')->getData()){
                    $this->createSavedContact($cobuyer);
                }
            }

            //Устанавливаем статус у заявки и подзаявок на "Сохранена"
            $offer->setStatus(Offer::OFFER_STATUS['Сохранена']);
            $offer->getCalculateds()->map(function($calc) use ($em){
                $calc->setStatus(Offer::OFFER_STATUS['Сохранена']);
                $em->persist($calc);
            });

            $em->persist($offer);
            $em->flush();
            $this->addFlash(
                'info',
                'Заявка успешно сохранена. Теперь необходимо загрузить документы и отправить на проверку'
            );
            return $this->redirectToRoute('offer_docs', ["id" => $offer->getId()]);
        }

        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
            'cobuyersform' => $cobuyersForm->createView(),
        ]);
    }


    /**
     * @Route("/offer/copy/{id}", name="offer_copy_calc")
     */
    public function copy_offer_calc(
        Calculated $calculated,
        Request $request,
        CalculatorService $calculatorService,
    ){
        $offer = $calculated->getOffer();
        $this->denyAccessUnlessGranted('edit', $offer);
        $form = $this->createForm(CalcType::class, $offer);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $offer->time = intval($form->get('time')->getData());
            $offer->cost = intval($form->get('cost')->getData());
            $offer->firstpay = intval($form->get('firstpay')->getData());
            $offer->motherCapSize = intval($form->get('motherCapSize')->getData());

        } else {
            /**
             * Сохраняем введенные срок кредита, из подзаявки в заявку
             */
            $offer->time = $calculated->getMonthcount();
            $offer->cost = $calculated->getFullsumm() + $calculated->getFirstpay();
            $offer->firstpay = $calculated->getFirstpay();
            $offer->motherCapSize = $calculated->getMotherCapSize();

            /**
             * Перебрасываем доп. поля в форму
             */
            $form->get('cost')->setData($offer->cost);
            $form->get('firstpay')->setData($offer->firstpay);
            $form->get('motherCapSize')->setData($offer->motherCapSize);
            $form->get('isMotherCap')->setData($offer->getIsMotherCap());
            $form->get('time')->setData($offer->time);
        }

        /**
         * Список банков для вывода в шаблоне получаем из сервиса.
         * Сервис возвращает массив сущностей BankMain
         */
        $resultBanks = $calculatorService->getBanksResult($offer);

        return $this->render('calculator/index_new.html.twig', [
            'calcForm' => $form->createView(),
            'bankList' => $resultBanks ?? [],
            'pagetitle' => "Копия заявки №" . $offer->getId() . ". Параметры кредита",
            'isCopy' => true,
            'offer' => $offer,
        ]);
    }


    /**
     * @Route("/offer/copy/{id}/create", name="offer_copy_create")
     */
    public function copy_offer_create(Offer $offer, Request $request, OfferService $offerService, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('edit', $offer);
        $emptyOffer = new Offer();
        $form = $this->createForm(CalcType::class, $emptyOffer);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newOffer = $offerService->generateOffer($form, $emptyOffer, $offer->getUser());

            $offerService->addCopyData($offer, $newOffer);

            $em->persist($newOffer);
            $em->flush();

            $this->addFlash(
                'info',
                'Копия заявки создана. Проверьте данные о заемщике, и сохраните её'
            );
            return $this->redirectToRoute('offer_success', ["id" => $newOffer->getId()]);

        }
    }





    /**
     * Сохраняет контакт, если среди сохраненных контактов
     * нет совпадающего (ФИО и номер телефона и номер паспорта)
     */
    private function createSavedContact($buyer) : Savedcontact
    {
        $savedRep = $this->em->getRepository(Savedcontact::class);
        $saved = new Savedcontact();
        $saved->setFirstname($buyer->getFirstname());
        $saved->setLastname($buyer->getLastname());
        $saved->setMiddlename($buyer->getMiddlename());
        $saved->setPhone($buyer->getPhone());
        $saved->setPasportSeries($buyer->getPasportSeries());
        $saved->setPasportNum($buyer->getPasportNum());
        $saved->setPasportDate($buyer->getPasportDate());
        $saved->setPasportCode($buyer->getPasportCode());
        $saved->setPasportDescript($buyer->getPasportDescript());
        $saved->setCreator($buyer->getCreator());
        $saved->setBirthDate($buyer->getBirthDate());
        $saved->setAddress($buyer->getAddress());
        $saved->setPassportAddress($buyer->getPassportAddress());
        $this->savedContact = $saved;

        $existContacts = $savedRep->findBy([
            'creator' => $this->getUser(),
        ]);
        $search = array_filter($existContacts, function($el){
            $svd = $this->savedContact;
            if(
                $el->getFirstname() === $svd->getFirstname() &&
                $el->getLastname() === $svd->getLastname() &&
                $el->getPhone() === $svd->getPhone() &&
                $el->getPasportNum() === $svd->getPasportNum()
            ) return true;
            return false;
        });
        if(!count($search)){
            $this->em->persist($saved);
            $this->em->flush();
        }
        return $saved;
    }
}
