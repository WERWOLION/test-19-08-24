<?php

namespace App\Controller;

use App\Entity\Bank;
use App\Entity\Offer;
use App\Form\CalcType;
use App\Entity\ChatRoom;
use App\Entity\Calculated;
use App\Form\On2DocFormType;
use Psr\Log\LoggerInterface;
use App\Service\OfferService;
use App\Service\UploaderService;
use App\Repository\OfferRepository;
use App\Service\bitrix24\BitrixService;
use App\Service\Logs\LogService;
use App\Service\ReferalsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/offers")
 */
class OffersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @Route("/index", name="offer_index", methods={"GET"})
     */
    public function index(OfferRepository $offersRep): Response
    {
        if (!$this->isGranted('isUserEmailConfirmed')) return $this->redirectToRoute('email_not_active');
        return $this->redirectToRoute('suboffer_index');
    }


    /**
     * @Route("/{id}", name="offer_show", methods={"GET"})
     */
    public function show(Offer $offer): Response
    {
        if (!$this->isGranted('edit', $offer)) {
            $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        if ($offer->getStatus() <= 10) {
            return $this->redirectToRoute('offer_success', ['id' => $offer->getId()]);
        }
        return $this->render('offers/show.html.twig', [
            'offer' => $offer,
        ]);
    }


    /**
     * @Route("/{id}/docs", name="offer_docs", methods={"GET"})
     */
    public function docs(Offer $offer, OfferService $offerService): Response
    {
        $this->denyAccessUnlessGranted('edit', $offer, 'У вас нет права на редактирование заявки');
        $dublicates = $offerService->findDublicates($offer);
        $limitisReach = false;
        if (count($dublicates) + $offer->getCalculateds()->count() > 2) {
            $limitisReach = true;
        }

        $otherData = $offer->getOther();
        if (!$otherData) $otherData = [];
        $offer->setOther($otherData);
        $is2DocForm = $this->createForm(On2DocFormType::class, $offer, [
            'action' => $this->generateUrl('offer_2doc_form', ['id' => $offer->getId()])
        ]);

        return $this->render('offers/show_docs.html.twig', [
            'offer' => $offer,
            'dublicates' => $dublicates,
            'isLimit' => $limitisReach,
            'is2DocForm' => $is2DocForm->createView(),
        ]);
    }


    /**
     * @Route("/{id}/sendis2doc", name="offer_2doc_form", methods={"POST"})
     */
    public function offer_2doc_form(Offer $offer, Request $request, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('edit', $offer, 'У вас нет права на редактирование заявки');
        $is2DocForm = $this->createForm(On2DocFormType::class, $offer, [
            'action' => $this->generateUrl('offer_2doc_form', ['id' => $offer->getId()])
        ]);
        $is2DocForm->handleRequest($request);
        if ($is2DocForm->isSubmitted() && $is2DocForm->isValid()) {

            $otherData = $offer->getOther();
            $otherData['is2docWorkValid'] = true;

            $offer->setOther($otherData);
            $em->persist($offer);
            $em->flush();
        }
        return $this->render('offers/_on2doc_form.html.twig', [
            'offer' => $offer,
            'is2DocForm' => $is2DocForm->createView(),
        ]);
    }


    /**
     * @Route("/{id}/docs/reload", name="offer_doc_reload", methods={"GET"})
     */
    public function offer_doc_reload(Offer $offer, OfferService $offerService): Response
    {
        $this->denyAccessUnlessGranted('edit', $offer, 'У вас нет права на редактирование заявки');
        $dublicates = $offerService->findDublicates($offer);
        $limitisReach = false;
        if (count($dublicates) + $offer->getCalculateds()->count() > 2) {
            $limitisReach = true;
        }
        return $this->render('offers/_docsbox.html.twig', [
            'offer' => $offer,
            'dublicates' => $dublicates,
            'isLimit' => $limitisReach,
        ]);
    }

    /**
     * @Route("/{id}/send", name="offer_send", methods={"POST"})
     */
    public function send(
        Request $request,
        Offer $offer,
        BitrixService $bitrixService,
        OfferService $offerService,
        EntityManagerInterface $em,
        LogService $logService,
        ReferalsService $referalsService,
    ): Response {
        $this->denyAccessUnlessGranted('edit', $offer, 'У вас нет права на редактирование заявки');
        if ($offer->getStatus() < 10 || !count($offer->getDocuments())) {
            return $this->json([
                'detail' => 'К заявке не загружены документы заёмщика или незаполнены данные заёмщика'
            ], 400);
        }
        $dublicates = $offerService->findDublicates($offer);
        if (count($dublicates) + $offer->getCalculateds()->count() > 2) {
            return $this->json([
                'detail' => 'Вы можете иметь не более двух заявок на одного заёмщика одновременно',
            ], 400);
        }
        foreach ($offer->getCalculateds() as $calculated) {
            try {
                $isPersonal = json_decode($request->getContent(), true)? json_decode($request->getContent(), true)["isPersonal"] : 0;
                $calculated = $bitrixService->dealCreate($calculated, $isPersonal);
                $calculated->setStatus($offer::OFFER_STATUS['Отправлена']);
                $chat = $this->create_chatroom($calculated);
                $em->persist($chat);
                $calculated->setChatRoom($chat);
                $em->persist($calculated);
                $log = $logService->calcLog($calculated->getId(), 'Заявка отправлена', 'Заявка отправлена в банк', false);

                if ($isPersonal) {
                    $currUser = $offer->getUser()->getPartner()->getMyReferal();

                    $currTransaction = $referalsService->getCurrTransaction($offer->getUser()->getPartner(), $currUser);

                    if ($currTransaction) {
                        $currTransaction->setAmount(3000);
                        $em->persist($currTransaction);
                    }

                    $bitrixService->updateReferalDealBonus($offer->getUser()->getPartner(), 3000);
                    $bitrixService->updateReferalDealStage($offer->getUser()->getPartner(), 1, 2);
                } else {
                    $bitrixService->updateReferalDealStage($offer->getUser()->getPartner(), 1, 2);
                }

                $em->persist($log);
            } catch (\Throwable $th) {
                return $this->json([
                    'detail' => $th->getMessage() ? $th->getMessage() : 'Ошибка. Не удалось отправить заявку.',
                    'redirect' => $this->generateUrl('offer_docs', ['id' => $offer->getId()]),
                ], 400);
            }
        }

        $offer->setStatus($offer::OFFER_STATUS['Отправлена']);
        $em->persist($offer);
        $em->flush();

        return $this->json([
            'detail' => "ok",
            'bitrixUrl' => $this->generateUrl('bitrix_person_doc_upload', ["id" => $offer->getId()]),
            'redirect' => $this->generateUrl('suboffer_index'),
        ]);
    }


    /**
     * @Route("/{id}/docupload", name="offer_upload", methods={"POST"})
     */
    public function sendDocs(Offer $offer, UploaderService $uploadService, Request $request): Response
    {
        $files = $request->files->get('docs');
        $description = $request->get('description');
        if ($files) {
            $result = $uploadService->upload($files, $this->getUser(), $description, $offer);
            return $this->json($uploadService->getUppyOutput($result));
        }
        throw new \Exception("Файла нет...", 1);
    }


    /**
     * @Route("/{id}/doc_delete", name="offer_doc_delete", methods={"POST"})
     */
    public function deleteDocs(Offer $offer, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('edit', $offer, "Запрошено удаление заявки другого пользователя");
        $docs = $offer->getDocuments();
        foreach ($docs as $key => $attachment) {
            $offer->removeDocument($attachment);
            $em->remove($attachment);
            $em->persist($offer);
        }
        $em->flush();
        return $this->json([
            'status' => "ok",
        ]);
    }



    /**
     * @Route("/{id}", name="offer_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->request->get('_token'))) {

            $this->denyAccessUnlessGranted('edit', $offer, "Запрошено удаление заявки другого пользователя");

            if (in_array($offer->getStatus(), [0, 10])) {
                //Если заявка червновик или не отправлена то совсем удаляем её
                return $this->redirectToRoute('offer_remove', ['id' => $offer->getId()]);
            }

            $offer->setStatus(-30);
            foreach ($offer->getCalculateds() as $calc) {
                $calc->setStatus(-30);
                if ($calc->getChatRoom()) {
                    $entityManager->remove($calc->getChatRoom());
                }
            }
            $entityManager->persist($offer);
            $entityManager->flush();
        }
        $this->addFlash(
            'info',
            'Заявка отменена. Найти её можно в разделе Отмененные'
        );
        return $this->redirectToRoute('offer_index');
    }


    /**
     * @Route("/{id}/remove", name="offer_remove", methods={"GET", "DELETE"})
     */
    public function admin_remove(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('edit', $offer)) {
            throw $this->createAccessDeniedException("Запрошено удаление заявки другого пользователя");
        }
        $calculateds = $offer->getCalculateds();
        foreach ($calculateds as $calculated){
            $em->remove($calculated);
            $em->flush();
        }
        $em->remove($offer);
        $em->flush();
        $this->addFlash(
            'info',
            'Заявка удалена навсегда'
        );
        return $this->redirectToRoute('offer_index');
    }

    /**
     * @Route("/{id}/status/{status}", name="offer_status", methods={"GET"})
     */
    public function admin_status(int $id, int $status, Request $request, EntityManagerInterface $em, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('ROLE_MANAGER')) throw $this->createAccessDeniedException("Нет доступа");
        $offerRep = $this->getDoctrine()->getRepository(Offer::class);
        $offer = $offerRep->find($id);
        if (!$offer) $this->createNotFoundException("Не найдено");
        $offer->setStatus($status);

        if ($status == 130) {
            $bitrixService->updateReferalDealStage($offer->getUser()->getPartner(), 2, 3);
        }

        foreach ($offer->getCalculateds() as $calc) {
            $calc->setStatus($status);
        }
        $em->persist($offer);
        $em->flush();

        $this->addFlash(
            'info',
            'Статус изменен на ' . $status,
        );
        return $this->redirectToRoute('offer_index');
    }




    private function create_chatroom(Calculated $calc): ChatRoom
    {
        if ($calc->getChatRoom()) {
            return $calc->getChatRoom();
        }
        $title = $calc->getOffer()->getId() . "-" . $calc->getId();
        $chat = new ChatRoom();
        $chat->setUser($calc->getOffer()->getUser());
        $chat->setFio($calc->getOffer()->getBuyer()->getFio(true));
        $chat->setCalculated($calc);
        $calc->setChatRoom($chat);
        $chat->setTitle($title);
        $chat->addViewedByUser($calc->getOffer()->getUser());
        if ($calc->getOffer()->getUser()->getMyManager()) {
            $chat->addViewedByUser($calc->getOffer()->getUser()->getMyManager());
        }
        return $chat;
    }
}
