<?php

namespace App\Controller;
use App\Entity\Offer;
use App\Entity\Attachment;
use App\Entity\Calculated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AttachmentsController extends AbstractController
{
    /**
     * @Route("/attachment/{id}/delete", name="file_delete", methods={"GET", "DELETE"})
     */
    public function file_delete(Attachment $attachment, Request $request)
    {   
        if($this->getUser()->getId() !== $attachment->getUser()->getId()){
            throw $this->createNotFoundException('Не удалось удалить файл. Он пренадлежит другому пользователю');
        }   
        $em = $this->getDoctrine()->getManager();
        $em->remove($attachment);
        $em->flush();
        return $this->json([
            'status' => 'ok',
        ]);
    }
}
