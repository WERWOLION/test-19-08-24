<?php

namespace App\Controller;
use App\Entity\Buyer;
use App\Entity\Savedcontact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ContactsController extends AbstractController
{
    /**
     * @Route("/contacts/index", name="my_buyer_index", methods={"GET"})
     */
    public function index(): Response
    {
        $buyerRep = $this->getDoctrine()->getRepository(Savedcontact::class);
        $myBuyers = $buyerRep->findBy(
            ['creator' => $this->getUser()],
        );
        // dd($myBuyers);
        return $this->render('buyers/index.html.twig', [
            'buyers' => $myBuyers,
        ]);
    }

    /**
     * @Route("/contacts/{id}/delete", name="my_buyer_delete", methods={"DELETE"})
     */
    public function delete(Savedcontact $contact, Request $request): Response
    {
        $em = $this->getDoctrine()->getManager();
        
        if(!$this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            return $this->createNotFoundException('Ошибка. Неверный токен');
        }
        if($contact->getCreator() != $this->getUser()){
            return $this->createNotFoundException('Ошибка. Контакт принадлежит другому пользователю');
        }
        $em->remove($contact);
        $em->flush();
        return $this->redirectToRoute('my_buyer_index');
    }




    /**
     * @Route("/byuer/{id}/accept_doc", name="buyer_accept_pdf", methods={"GET"})
     */
    public function byuer_accept_doc(
        Buyer $buyer,
        DompdfWrapperInterface $wrapper
    ): Response
    {
        if(
            $buyer->getCreator() !== $this->getUser() && 
            !$this->isGranted('ROLE_ADMIN') && 
            !$this->isGranted('ROLE_MANAGER')
        ){
            throw $this->createNotFoundException('Ошибка. Нет доступа');
        }
        $html = $this->renderView('buyers/pdf_accepter.html.twig', [
            'buyer' => $buyer,
        ]);
        $docName = $buyer->getFirstname() . "-" .$buyer->getLastname() . '_personal_data.pdf';
        $response = $wrapper->getStreamResponse($html, $docName, [
            "Attachment" => false,
        ]);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->send();
        return $response;
    }
}
