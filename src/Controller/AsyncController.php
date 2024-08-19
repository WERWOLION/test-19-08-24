<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Calculated;
use App\Service\bitrix24\BitrixService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/async")
 */
class AsyncController extends AbstractController
{
    /**
     * @Route("/{id}/person_doctobitrix", name="bitrix_person_doc_upload", methods={"POST"})
     */
    public function sendPersonDocsToBitrix(Offer $offer, Request $request, BitrixService $bitrixService): Response
    {
        $data = $bitrixService->dealUploadPersonDocs($offer);
        return $this->json($data);
    }


    /**
     * @Route("/{id}/object_doctobitrix", name="bitrix_object_doc_upload", methods={"POST"})
     */
    public function sendObjectDocsToBitrix(Calculated $calculated, Request $request, BitrixService $bitrixService): Response
    {
        $bitrixService->dealUploadObjectDocs($calculated);
        return $this->json([
            'ok',
        ]);
    }
}
