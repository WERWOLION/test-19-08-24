<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Form\PartnerType;
use App\Form\ProfileType;
use App\Entity\Attachment;
use App\Entity\Calculated;
use App\Entity\MoneyRequest;
use App\Service\OfferService;
use App\Service\PartnerService;
use App\Service\ReferalsService;
use App\Service\UploaderService;
use App\Form\RegistrationFormType;
use App\Repository\MoneyRequestRepository;
use App\Repository\TownRepository;
use App\Service\Wallet\WalletService;
use App\Service\bitrix24\BitrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/", name="my_profile")
     */
    public function index(): Response
    {
        if (!$this->isGranted('isUserEmailConfirmed')) {
            return $this->redirectToRoute('email_not_active');
        }
        return $this->render('profile/view.html.twig', []);
    }

    /**
     * @Route("/edit", name="profile_edit")
     */
    public function edit(Request $request, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('isUserEmailConfirmed')) {
            return $this->redirectToRoute('email_not_active');
        }

        /**
         * @var User $me
         */
        $me = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(ProfileType::class, $me);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($me);
            $entityManager->flush();

            $bitContID = false;
            if ($me->getPartner()->getBitrixContactID()) {
                $bitContID = $bitrixService->contactUpdatePartner($me->getPartner());
            }
            if ($bitContID) {
                $this->addFlash(
                    'notice',
                    'Ваш профиль успешно отредактирован'
                );
            } else {
                $this->addFlash(
                    'error',
                    'Ошибка сохранения профиля. Обратитесь к вашему кредитному специалисту'
                );
            }
            return $this->redirectToRoute('profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }



    /**
     * @Route("/partner/new", name="partner_new")
     */
    public function partner_new(Request $request): Response
    {
        if (!$this->isGranted('isUserEmailConfirmed')) {
            return $this->redirectToRoute('email_not_active');
        }
        return $this->render('profile/partner_new.html.twig', []);
    }



    /**
     * @Route("/partner/edit", name="partner_edit")
     */
    public function partner(Request $request, BitrixService $bitrixService, PartnerService $partnerService): Response
    {
        if (!$this->isGranted('isUserEmailConfirmed')) {
            return $this->redirectToRoute('email_not_active');
        }
        if (!$this->isGranted('isPartnerActive')) {
            try {
                $partner = $partnerService->partnerGetStartData($this->getUser(), intval($request->get('partnertype')));
            } catch (\Throwable $th) {
                $this->addFlash(
                    'info',
                    'Вам необходимо получить статус партнера'
                );
                return $this->redirectToRoute('partner_new');
            }
        } else {
            $partner = $this->getUser()->getPartner();
            if (intval($request->get('partnertype')) && in_array(intval($request->get('partnertype')), [1, 2, 3, 4])) {
                $partner->setInn(null);
                $partner->setFullname('');
                $partner->setLegaladress('');
                $partner->setContactface("");
                $partner->setOgrn("");
                $partner->setType(intval($request->get('partnertype')));
            }
        }

        $form = $this->createForm(PartnerType::class, $partner, [
            "partner_type" => $partner->getType(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // if(!$partnerService->parnerTestOkved($partner)){
            //     $this->addFlash(
            //         'error',
            //         'Ошибка. У вас нет необходимых кодов ОКВЭД'
            //     );
            //     return $this->redirectToRoute('partner_edit', ['id' => $partner->getId()]);
            // }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($partner);

            if (!$this->getParameter('isBitrixActive')) {
                $partner->setBitrixContactID('test');
            } else {
                $setDocs = "1";

                if (intval($request->get('partnertype')) == 1) {
                    $setDocs = null;
                }

                $bitContID = $bitrixService->contactUpdatePartner($partner, $setDocs);
            }

            $entityManager->flush();
            $this->addFlash(
                'notice',
                'Данные партнера успешно сохранены'
            );

            if ($partner->getType() === 1) {
                return $this->redirectToRoute('partner_documents');
            } else {
                return $this->redirectToRoute('my_profile');
            }
        }
        return $this->render('profile/partner_edit.html.twig', [
            'partnerForm' => $form->createView(),
            'new_partner' => $partner,
        ]);
    }



    /**
     * @Route("/partner/documents", name="partner_documents")
     */
    public function partner_docs(Request $request): Response
    {
        return $this->render('profile/partner_documents.html.twig', []);
    }


    /**
     * @Route("/partner/upload", name="partner_upload", methods={"POST"})
     */
    public function partner_upload(Request $request, UploaderService $uploadService, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->createAccessDeniedException('Вы не авторизированы');
        }
        if (!$this->isGranted('isUserEmailConfirmed')) {
            return $this->createAccessDeniedException('Ваш email не подтвержден');
        }
        if (!$this->getUser()->getPartner()) {
            return $this->createAccessDeniedException('Вы не зарегистрированы как партнер');
        }

        $files = $request->files->get('docs');
        $partner = $this->getUser()->getPartner();

        if ($files) {
            $em = $this->getDoctrine()->getManager();
            $attachment = $uploadService->upload($files, $this->getUser());
            $attachment->setFoldername('users');
            $attachment->setPartnerlink($partner);
            $em->persist($attachment);
            $em->flush();

            if ($partner->getType() == 1) {
                $bitrixService->contactUpdatePartner($partner, "1");
            }

            return $this->json($uploadService->getUppyOutput($attachment));
        }
        throw new \Exception("Файла нет...", 1);
    }

    /**
     * @Route("/referals", name="referals", methods={"GET"})
     */
    public function referals(
        ReferalsService $referalsService,
        OfferService $offerService,
        MoneyRequestRepository $moneyRequestRepository,
        WalletService $walletService,
        BitrixService $bitrixService,
        EntityManagerInterface $em
    ) {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $partner = $user->getPartner();
        $referals = $partner->getReferals();
        $amountArray = [];

        foreach ($referals as $key => $referalPartner) {
            $currReferalStatus = $referalPartner->getReferalStatus();

            // Если реферал находится на стадии 
            if ($currReferalStatus == 5) {
                $dealCurrStage = $bitrixService->getReferalCurrStage($referalPartner->getUser()->getPartner());

                if ($dealCurrStage == 6) {
                    $bitrixService->updateReferalDealStage($referalPartner->getUser()->getPartner(), 5, 6);

                    $referalCurrBalance = $bitrixService->getReferalbalanceDeal($referalPartner->getUser()->getPartner());

                    $userCurrBalance = $user->getWallet()->getBalanceReady();

                    $user->getWallet()->setBalanceReady($userCurrBalance - $referalCurrBalance);

                    $moneyRequestCurr = new MoneyRequest();

                    $moneyRequestCurr->setWallet($user->getWallet());
                    $moneyRequestCurr->setAmount($referalCurrBalance);
                    $moneyRequestCurr->setStatus(20);

                    $user->getWallet()->addMoneyRequest($moneyRequestCurr);

                    $em->persist($moneyRequestCurr);
                    $em->persist($user);
                    $em->flush();
                }
            }

            $currTransaction = $referalsService->getCurrTransaction($referalPartner, $partner);

            if ($currTransaction) {
                $amountArray[$key] = $currTransaction->getAmount();
            } else {
                $amountArray[$key] = 10000;
            }

            $offerService->fillCalculatedToUser($referalPartner->getUser());
        }

        $moneyRequests = $moneyRequestRepository->findBy([
            'status' => 20,
            'wallet' => $user->getWallet(),
        ], ['createdAt' => 'asc']);

        $walletService->recalculateWallet($user->getWallet());

        if (!$partner) $this->redirectToRoute('lk');
        return $this->render('profile/referals.html.twig', [
            'link' => $referalsService->getReferalLink($partner),
            'holdSumm' => $referalsService->getHoldReferalSumm($partner),
            'referals' => $referals,
            'testOffer' => new Offer(),
            'moneyRequests' => $moneyRequests,
            'amountArray' => $amountArray,
        ]);
    }

    /**
     * @Route("/send/referals", name="partner_send_referals", methods={"POST"})
     */
    public function partner_send_referals(
        BitrixService $bitrixService
    ) {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $partner = $user->getPartner();
        $referals = $partner->getReferals();
        foreach ($referals as $referalPartner) {
            $bitrixService->updateReferalDealStage($referalPartner, 4, 5);
        }

        return $this->json("ok");
    }


    /**
     * @Route("/make/{id}/{password}", name="partner_make_exist", methods={"GET"})
     */
    public function partner_make_exists(
        int $id,
        string $password,
        BitrixService $bitrixService,
        PartnerService $partnerService,
        UserPasswordHasherInterface $passwordHasher,
        TownRepository $townRepository
    ) {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');
        $exsist = $bitrixService->contactGet($id);
        if (!$exsist['ID']) dd($exsist);

        $user = new User();
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $password,
            )
        );
        // dd($exsist);
        $town = $townRepository->find(54);
        $user->setTown($town);
        $user->setEmail($exsist['EMAIL'][0]['VALUE']);
        $user->setFirstname($exsist['NAME']);
        $user->setLastname($exsist['LAST_NAME']);
        $user->setMiddlename($exsist['SECOND_NAME']);
        $user->setPhone(str_replace(["-", " ", "(", ")"], "", $exsist['PHONE'][0]['VALUE']));
        $user->setRoles(["ROLE_USER"]);
        $user->setIsEmailConfirm(true);
        $partnerService->PartnerInitNew($user);
        dd($user);
    }
}
