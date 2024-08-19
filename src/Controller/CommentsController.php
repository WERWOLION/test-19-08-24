<?php
namespace App\Controller;
use App\Entity\Calculated;
use App\Service\bitrix24\BitrixService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/comment")
 */
class CommentsController extends AbstractController
{
    /**
     * @Route("/add/{id}", name="comment_create", methods={"POST"})
     */
    public function new(Calculated $calc, Request $request, BitrixService $bitrixService): Response
    {
        $message = $request->toArray()['comment'];
        $comment = $bitrixService->commentsAdd($calc, $message);
        return $this->redirectToRoute('comment_load', ["id" => $calc->getId()], 307);
    }


    /**
     * @Route("/load/{id}", name="comment_load", methods={"POST"})
     */
    public function load(Calculated $calculated, Request $request, BitrixService $bitrixService)
    {
        $comments = $bitrixService->сommentsGet($calculated);
        return $this->render('inc/_comments.html.twig', [
            'comments' => $comments,
        ]);
    }
}