<?php
namespace App\Controller\Api;

use Pusher\Pusher;
use App\Entity\User;
use App\Entity\ChatRoom;
use App\Entity\Calculated;
use App\Entity\ChatMessage;
use App\Service\UploaderService;
use App\Repository\UserRepository;
use App\Repository\ChatRoomRepository;
use App\Service\bitrix24\BitrixService;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api/v1/chat")
 */
class ChatApi extends AbstractController
{
    private $em;
    private $serializer;
    private $userRep;
    private $bitrixService;
    private $jsonConst = ['json_encode_options'=> JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT ];

    public function __construct(
        EntityManagerInterface $em,
        SerializerInterface $serializer,
        UserRepository $userRepository,
        BitrixService $bitrixService
    ) {
        $this->em = $em;
        $this->userRep = $userRepository;
        $this->serializer = $serializer;
        $this->bitrixService = $bitrixService;
    }


    /**
     * @Route("/clients", name="chat_partner_index", methods={"GET"})
     */
    public function chat_partners_index()
    {
        if($this->isGranted('ROLE_MANAGER')){
            if($this->isGranted('ROLE_ADMIN')){
                $clients = $this->userRep->findOnlyClients();
            } else {
                $clients = $this->getUser()->getMyAdminedUsers()->toArray();
            }
        } else {
            $clients = [$this->getUser()];
        }
        $clientsSorted = $this->addUserNewMessageMarker($clients);

        $outPartners = array_map(fn($user) => $this->serivalizePartner($user), $clientsSorted);

        return $this->json($outPartners, 200, [], $this->jsonConst);
    }



    /**
     * @Route("/rooms", name="chat_room_index", methods={"GET"})
     */
    public function my_chatroom_index(ChatRoomRepository $chatRep)
    {
        // $chatListRaw = $chatRep->getMyChatRooms($this->getUser());
        $chatListRaw = $chatRep->findAll();
        $chatList = array_map(function($room){
            return $this->serivalizeChatRoom($room);
        }, $chatListRaw);
        return $this->json($chatList, 200, [], $this->jsonConst);
    }


    /**
     * @Route("/client/{id}/rooms", name="client_rooms", methods={"GET"})
     */
    public function client_chatrooms(User $user, ChatRoomRepository $chatRep)
    {
        $chatListRaw = $user->getChatRooms()->toArray();
        $chatList = array_map(function($room){
            return $this->serivalizeChatRoom($room);
        }, $chatListRaw);
        return $this->json($chatList, 200, [], $this->jsonConst);
    }


    /**
     * @Route("/room/{id}", name="room_messages", methods={"GET"})
     */
    public function room_mesages(ChatRoom $chatRoom, ChatRoomRepository $chatRep)
    {
        $messageRaw = $chatRoom->getMessages()->toArray();
        $messages = array_map(function($message){
            return $this->serivalizeMessage($message);
        }, $messageRaw);

        /**
         * Если пользователя нет среди посмотревших диалог,
         * то добавляем его в список посмотревших, чтобы снять бейджик в чате
         */
        if(!$chatRoom->getViewedByUsers()->contains($this->getUser())){
            $chatRoom->addViewedByUser($this->getUser());
            $this->em = $this->getDoctrine()->getManager();
            $this->em->persist($chatRoom);
            $this->em->flush();
        }

        return $this->json($messages, 200, [], $this->jsonConst);
    }


    /**
     * @Route("/room/{id}/send", name="chat_send_message", methods={"POST"})
     */
    public function chat_send_message(ChatRoom $chatroom, Request $request, Pusher $pusher, BitrixService $bitrixService)
    {
        $parameters = json_decode($request->getContent(), true);
        if(!isset($parameters['message'])) throw new \Exception('Сообщение не передано');
        $newMessage = $parameters['message'];
        $clearNoBrMessage = filter_var($newMessage, FILTER_SANITIZE_STRING);
        $clearMessage = nl2br($clearNoBrMessage);
        if(!$clearMessage) throw new \Exception('Сообщение не прошло проверку');

        $chatMess = new ChatMessage();
        $chatMess->setUser($this->getUser());
        $chatMess->setContent($clearMessage);
        $chatMess->setChatRoom($chatroom);
        if($this->isGranted('ROLE_MANAGER')){
            $chatMess->setIsAdminMessage(true);
        }
        $this->em->persist($chatMess);

        $chatroom->clearViewedByUser();
        $chatroom->addViewedByUser($this->getUser());
        $this->em->persist($chatroom);
        $this->em->flush();

        $pusher->trigger('chat', 'message', $this->serivalizeMessage($chatMess));

        if($this->getParameter('isBitrixActive') && !$this->isGranted('ROLE_MANAGER')){
            $this->sendBitrixNotyf($chatroom);
        }
        return $this->json($this->serivalizeMessage($chatMess), 200, [], $this->jsonConst);
    }













    private function serivalizePartner(User $user)
    {
        return [
            'id' => $user->getId(),
            'fio' => $user->getFio(),
            'phone' => $user->getPhone(),
            'email' => $user->getEmail(),
            'isNewMess' => $user->isNewMess,
            'messageCount' => $user->messageCount,
            // 'chatrooms' => $user->getChatRooms()->map(fn($chat) => $this->serivalizeChatRoom($chat)),
        ];
    }
    private function serivalizeChatRoom(ChatRoom $chat)
    {
        return [
            'title' => $chat->getTitle(),
            'bankname' => $chat->getCalculated() ? $chat->getCalculated()->getBank()->getTitle() : null,
            'created' => $chat->getCreatedAt(),
            'calcId' => $chat->getCalculated() ? $chat->getCalculated()->getId() : null,
            'chatId' => $chat->getId(),
            'isOpen' => $chat->getIsOpen(),
            'isGeneric' => $chat->getIsGenericDialog(),
            'isViewed' => $chat->getViewedByUsers()->contains($this->getUser()),
            'bitrixID' => $chat->getCalculated() ? $chat->getCalculated()->getBitrixID() : null,
            'name' => $chat->getFio() ? $chat->getFio() : $chat->getCalculated()?->getOffer()?->getBuyer()?->getFio(),
            'count' => $chat->getMessages()->count(),
        ];
    }
    private function serivalizeMessage(ChatMessage $message)
    {
        return [
            'id' => $message->getId(),
            'createdAt' => $message->getCreatedAt()->format('c'),
            'content' => $message->getContent(),
            'isNew' => $message->getIsNew(),
            'isAdminMessage' => $message->getIsAdminMessage(),
            'isMyMessage' => $message->getUser() === $this->getUser(),
            'user' => $message->getUser()->getFio(),
            'userId' => $message->getUser()->getId(),
            'chatRoom' => $message->getChatRoom()->getId(),
        ];
    }

    //Определяем есть ли новые сообщения
    private function addUserNewMessageMarker($clients)
    {
        foreach ($clients as $client) {
            $client->isNewMess = false;
            $client->messageCount = 0;
            $rooms = $client->getChatRooms();
            foreach ($rooms as $room) {
                $client->messageCount += $room->getMessages()->count();
                if(!$room->getViewedByUsers()->contains($this->getUser())){
                    $client->isNewMess = true;
                    break;
                }
            }
            if(!$client->messageCount) $client->isNewMess = false;
        }
        usort($clients, function($a, $b){
            if($a->getChatMessages()->isEmpty() && !$b->getChatMessages()->isEmpty()) return 1;
            if(!$a->getChatMessages()->isEmpty() && $b->getChatMessages()->isEmpty()) return -1;
            if($a->getChatMessages()->isEmpty() && $b->getChatMessages()->isEmpty()) return 0;
            return $a->getChatMessages()->last()->getCreatedAt()->getTimestamp() < $b->getChatMessages()->last()->getCreatedAt()->getTimestamp();
        });
        return $clients;
    }

    public function sendBitrixNotyf(ChatRoom $chatRoom)
    {
        $currentManager = $this->getUser()->getMyManager();
        $currentManagerFio = "Не назначено";
        if($currentManager){
            $currentManagerFio = $currentManager->getFio();
        }
        $message = "Новое сообщение в чате ЛК: \n" .
        "по заявке «" . $chatRoom->getTitle() . "» \n" .
        "От пользователя: " . $this->getUser()->getEmail() . "\n" .
        "(ответственный: " . $currentManagerFio .")\n" .
        "Перейти в чат: " . $this->generateUrl(
            'admin_chat',
            ["room" => $chatRoom->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        try {
            $this->bitrixService->sendChatMessageToBitrix24($message);
            $this->bitrixService->sendNotyfToBitrix24($message, intval($currentManager->getBitrixManagerID()));
        } catch (\Throwable $th) {
            //TODO
        }
    }
}
