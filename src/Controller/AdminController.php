<?php

namespace App\Controller;
use Pusher\Pusher;
use App\Entity\User;
use App\Entity\ChatRoom;
use App\Repository\UserRepository;
use App\Repository\ChatRoomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class AdminController extends AbstractController
{
    /**
     * @Route("/manage/chat", name="admin_chat")
     */
    public function chat_index(ChatRoomRepository $chatRep, Request $request): Response
    {
        $id = intval($request->get("room"));
        if($id){
            $room = $chatRep->find($id);
            if(!$room) throw $this->createNotFoundException();
            $client = $room->getUser()->getId();
        }

        return $this->render('manage/chat.html.twig', [
            'room' => $room ?? null,
            'client' => $client ?? null,
        ]);
    }
}
