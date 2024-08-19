<?php

namespace App\Controller;
use Pusher\Pusher;
use App\Entity\ChatRoom;
use App\Entity\ChatMessage;
use App\Entity\Savedcontact;
use App\Repository\ChatRoomRepository;
use App\Service\bitrix24\BitrixService;
use App\Repository\CalculatedRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    /**
     * @Route("/bitrix/contact/{id}", name="get_bitrix_contact", methods={"GET"})
     */
    public function get_bitrix_contact(Int $id, Request $request, BitrixService $bitrixService): Response
    {
        $contact = $bitrixService->contactGet($id);
        return $this->json($contact, 200, [], ['json_encode_options'=> JSON_UNESCAPED_UNICODE]);
    }


    /**
     * @Route("/contacts/index", name="my_buyer_json", methods={"GET"})
     */
    public function api_index(): Response
    {
        $buyerRep = $this->getDoctrine()->getRepository(Savedcontact::class);
        $myBuyers = $buyerRep->findBy(
            ['creator' => $this->getUser()],
        );
        return $this->json($myBuyers, 200, [], ['json_encode_options'=> JSON_UNESCAPED_UNICODE]);
    }
    
    /**
     * @Route("/savepopup", name="save_popup", methods={"GET", "POST"})
     */
    public function save_popup(Request $request): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $absolutePath = $projectDir . '/public_html/popup-settings.json';
        $absoluteImagePath = $projectDir . '/public_html/img/';

        $requestData = $request->request;

        $popupsData = (array) json_decode(file_get_contents($absolutePath), true);

        $type = $requestData->get("type");
        $title = $requestData->get("title");
        $text = $requestData->get("text");
        $linkText = $requestData->get("link-text");
        $link = $requestData->get("link");

        $imgDesktop = $_FILES['img-desktop']['tmp_name'];
        $imgMobile = $_FILES['img-mobile']['tmp_name'];

        if (empty($popupsData[$type])) {
            return $this->json(["error" => "Type is not allowed"]);
        }

        if (!empty($title)) {
            $popupsData[$type]["title"] = $title;
        } else {
            $popupsData[$type]["title"] = "";
        }

        if (!empty($text)) {
            $popupsData[$type]["text"] = $text;
        } else {
            $popupsData[$type]["text"] = "";
        }

        if (!empty($linkText)) {
            $popupsData[$type]["link-text"] = $linkText;
        } else {
            $popupsData[$type]["link-text"] = "";
        }

        if (!empty($link)) {
            $popupsData[$type]["link"] = $link;
        } else {
            $popupsData[$type]["link"] = "";
        }

        if (!empty($imgDesktop)) {
            $imgName = $type . "_popup-image.jpg";
            $imgPath = $absoluteImagePath . $imgName;
            $popupsData[$type]["img-desktop"] = "/img/" . $imgName;
            move_uploaded_file($imgDesktop, $imgPath);
        }

        if (!empty($imgMobile)) {
            $imgName = $type . "_popup-image-mobile.jpg";
            $imgPath = $absoluteImagePath . $type . "_popup-image-mobile.jpg";
            $popupsData[$type]["img-mobile"] = "/img/" . $imgName;
            move_uploaded_file($imgMobile, $imgPath);
        }

        file_put_contents($absolutePath, json_encode($popupsData));

        $route = $request->headers->get('referer');

        return $this->redirect($route);
    }

    /**
     * @Route("/myoffers/index", name="my_offers_json", methods={"GET"})
     */
    public function my_offers_index(CalculatedRepository $calcsRep): Response
    {
        $selectedStatuses = [20,30,40,50,60,70,80,90,100,110,115,120,130];
        $myCalculateds = $calcsRep->findMyCalculatedWithStatus($selectedStatuses, $this->getUser());

        return $this->json($myCalculateds, 200, [], [
            'json_encode_options'=> JSON_UNESCAPED_UNICODE,
            'groups' => 'api_chat'
        ]);
    }

}