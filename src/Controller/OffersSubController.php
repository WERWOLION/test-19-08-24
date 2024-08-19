<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Calculated;
use App\Repository\BankMainRepository;
use App\Service\OfferService;
use App\Service\UploaderService;
use App\Service\bitrix24\BitrixService;
use App\Repository\CalculatedRepository;
use App\Service\Filter\RequestFilterDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/suboffer")
 */
class OffersSubController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em,
        private CalculatedRepository $calculatedRepository,
        private BankMainRepository $bankMainRepository,
    ) {
    }


    /**
     * @Route("/index", name="suboffer_index", methods={"GET"})
     */
    public function suboffer_index(Request $request)
    {
        $filter = RequestFilterDto::createFromRequest($request);
        if (!$request->get('order')) $filter->order = 'updatedAt';
        $filter->perPage = 24;
        $statuses = is_array($request->get('status')) ? $request->get('status') : [];
        $suboffers = $this->calculatedRepository->findMyFiltred($filter, $this->getUser(), $statuses);
        return $this->render('offers_sub/list.html.twig', [
            'items' => $suboffers['result'],
            'meta' => $suboffers['meta'],
        ]);
    }


    /**
     * @Route("/getnewevents", name="suboffer_new_events", methods={"GET"})
     */
    public function newEvents(CalculatedRepository $calcsRep)
    {
        $allMyCalc = $calcsRep->findMyNewCalcs($this->getUser());
        $eventStatus = array_map(function ($el) {
            if (in_array($el->getStatus(), [20, 30, 40, 50])) {
                return 2;
            }
            if (in_array($el->getStatus(), [60, 70, 80, 90, 100, 110, 115, 120])) {
                return 3;
            }
            if (in_array($el->getStatus(), [130])) {
                return 4;
            }
            if (in_array($el->getStatus(), [-30])) {
                return 5;
            }
            if (in_array($el->getStatus(), [-20, -10])) {
                return 6;
            }
        }, $allMyCalc);
        return $this->json($eventStatus);
        // dd($eventStatus);
    }



    /**
     * @Route("/{id}", name="suboffer_show", methods={"GET"})
     */
    public function show(
        Calculated $calculated,
        BitrixService $bitrixService,
        OfferService $offerService
    ): Response {
        $this->denyAccessUnlessGranted('edit', $calculated->getOffer(), 'У вас нет права на редактирование заявки');
        if ($calculated->getOffer() && $calculated->getStatus() === 10) {
            return $this->redirectToRoute('offer_docs', ['id' => $calculated->getOffer()->getId()]);
        }
        if ($this->getParameter('isBitrixActive')) {
            try {
                $folder = $bitrixService->dealDownload($calculated);
            } catch (\Throwable $th) {
                if (!($this->isGranted('ROLE_MANAGER'))) {
                    $offerService->calcDelete($calculated);
                    $this->addFlash(
                        'error',
                        'Ошибка. Заявка отсутствует или была удалена менеджером'
                    );
                    return $this->redirectToRoute('offer_index');
                }
            }
        }

        $isPersonal = $bitrixService->dealGet($calculated->getBitrixID())["UF_CRM_1681126317477"];
        $calcPrice = $bitrixService->dealGet($calculated->getBitrixID())["UF_CRM_1630503089"];

        if ($calculated->getStatus() == 10) {
            return $this->redirectToRoute('offer_docs', ['id' => $calculated->getOffer()->getId()]);
        }

        if ($calculated->getOffer()->getCreditTarget() === "залог") {
            $template = "zalog";
        } else {
            switch ($calculated->getOffer()->getSalerType()) {
                case 'физлицо':
                    $template = "fiz-flat";
                    if ($calculated->getOffer()->getObjectType() === "дом") {
                        $template = "fiz-house";
                    }
                    break;
                case 'юрлицо':
                    $template = "ur-flat";
                    if ($calculated->getOffer()->getObjectType() === "дом") {
                        $template = "ur-house";
                    }
                    break;
                case 'застройщик':
                    $template = "str-newflat";
                    if ($calculated->getOffer()->getObjectType() === "дом") {
                        $template = "str-newflat";
                    }
                    break;
                default:
                    $template = "fiz-flat";
                    break;
            }
        }
        if ($calculated->getOffer()->getCreditTarget() === "материнский") {
            $template = "mother";
        }
        return $this->render('offers_sub/show.html.twig', [
            'calculated' => $calculated,
            'templateName' => $template,
            'isPersonal' => $isPersonal,
            'calcPrice' => $calcPrice
        ]);
    }



    /**
     * @Route("/{id}/resend", name="suboffer_resend", methods={"GET"})
     */
    public function resend(Calculated $calculated, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            throw $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        if (!$calculated->getStatus() || $calculated->getStatus() !== 40) {
            throw new \Exception("Ошибка. Статус заявки не подходит");
        }
        //Устанавливаем в статус "Первичная проверка"
        $bitrixService->setBitrixStatus($calculated, 37);
        return $this->redirectToRoute('suboffer_show', ['id' => $calculated->getId()]);
    }




    /**
     * @Route("/{id}", name="suboffer_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Calculated $calculated, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            throw $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        if (!$calculated->getStatus()) {
            throw new \Exception("Ошибка. Заявка пока не разделена");
        }

        if ($this->isCsrfTokenValid('delete' . $calculated->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $calculated->setStatus(-30);
            $calculated->getChatRoom()->setIsOpen(false);
            $entityManager->persist($calculated);
            $entityManager->flush();
            $bitrixService->setBitrixStatus($calculated, array_search(-30, Offer::BITRIX24_STATUS));
        }
        $this->addFlash(
            'info',
            'Заявка отменена. Найти её можно в разделе Отмененные'
        );
        return $this->redirectToRoute('offer_index');
    }




    /**
     * @Route("/{id}/object_doc_upload", name="calculated_upload", methods={"POST"})
     */
    public function sendDocs(Calculated $calculated, UploaderService $uploadService, Request $request): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        $file = $request->files->get('docs');
        $description = $request->get('description');

        if ($file) {
            $em = $this->getDoctrine()->getManager();
            $result = $uploadService->upload($file, $this->getUser(), $description);
            $result->setFoldername("offer_" . $calculated->getOffer()->getId());
            $calculated->addObjectDoc($result);
            $em->persist($result);
            $em->persist($calculated);
            $em->flush();
            return $this->json($uploadService->getUppyOutput($result));
        }
        throw new \Exception("Файла нет...", 1);
    }


    /**
     * @Route("/{id}/objectdocs_send", name="objectdocs_send", methods={"GET"})
     * Меняет статус заявки, если документы загружены
     */
    public function objectdocsSend(Calculated $calculated, BitrixService $bitrixService): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            throw $this->createNotFoundException('У вас нет права на редактирование заявки');
        }

        if (!count($calculated->getObjectDocs())) {
            $this->addFlash(
                'error',
                'Ошибка. Документов на объект не загружено'
            );
            return $this->redirectToRoute('suboffer_show', ['id' => $calculated->getId()]);
        }
        $calculated->setStatus(80);
        $bitrixService->setBitrixStatus($calculated, array_search(80, Offer::BITRIX24_STATUS));
        $em = $this->getDoctrine()->getManager();
        $em->persist($calculated);
        $em->flush();
        $this->addFlash(
            'info',
            'Заявка обновлена. Объект недвижимости подобран'
        );
        return $this->redirectToRoute('suboffer_show', ['id' => $calculated->getId()]);
    }


    /**
     * @Route("/{id}/objectdocs_send", name="calculted_message_hide", methods={"POST"})
     * Делает сообщение в заявке просмотренным (устанавливаем в поле newEventType заявки значение 0)
     */
    public function messageHide(Calculated $calculated): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            throw $this->createNotFoundException('У вас нет права на просмотр заявки');
        }

        if ($this->isGranted("ROLE_ADMIN") || $this->isGranted("ROLE_MANAGER")) {
            return $this->json([
                'status' => "You Admin",
            ]);
        }

        $calculated->setNewEventType(0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($calculated);
        $em->flush();
        return $this->json([
            'status' => "ok",
        ]);
    }



    /**
     * @Route("/{id}/object_doc_delete", name="calculated_doc_delete", methods={"POST"})
     */
    public function deleteDocs(Calculated $calculated): Response
    {
        if (!$this->isGranted('edit', $calculated->getOffer())) {
            throw $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        $user = $this->getUser();

        $docs = $calculated->getObjectDocs();
        $em = $this->getDoctrine()->getManager();
        foreach ($docs as $key => $attachment) {
            $calculated->removeObjectDoc($attachment);
            $em->remove($attachment);
            $em->persist($calculated);
        }
        $em->flush();
        $this->addFlash(
            'info',
            'Документы на объект по заявке успешно удалены'
        );
        return $this->json([
            'status' => "ok",
        ]);
    }


    /**
     * @Route("/{id}/setsigndate", name="suboffer_signdate", methods={"POST"})
     */
    public function set_signdate(Calculated $calculated, Request $request, BitrixService $bitrixService): Response
    {
        $date = $request->get('signdate');
        $dateStd = date_create_immutable_from_format('d-m-Y H:i', $date);
        $dateStd = $dateStd->setTimezone(new \DateTimeZone('+0300'));
        // $dateStd = $dateStd->setTime(0,0,0,0);

        $calculated->setSignData($dateStd->format('c'));
        // $calculated->setStatus(120);
        $bitrixService->setSignDate($calculated, $dateStd->format('c'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($calculated);
        $em->flush();

        return $this->json([
            'std' => $dateStd,
        ]);
    }




    /**
     * @Route("/{id}/status/{status}", name="suboffer_status", methods={"GET"})
     */
    public function admin_status(int $id, int $status, Request $request): Response
    {
        if (!$this->isGranted("ROLE_MANAGER")) {
            $this->createAccessDeniedException('У вас нет права на редактирование заявки');
        }
        $subofferRep = $this->getDoctrine()->getRepository(Calculated::class);
        $suboffer = $subofferRep->find($id);
        if ($suboffer->getOffer()->getUser()->getId() !== $this->getUser()->getID()) {
            throw new \Exception("Запрошено изменение заявки другого пользователя");
        }
        if (!$suboffer) $this->createNotFoundException("Не найдено");

        $em = $this->getDoctrine()->getManager();
        $suboffer->setStatus($status);
        $em->persist($suboffer);
        $em->flush();

        $this->addFlash(
            'info',
            'Статус изменен на ' . $status,
        );
        return $this->redirectToRoute('suboffer_index');
    }
}
