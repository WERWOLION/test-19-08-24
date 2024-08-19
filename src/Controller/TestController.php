<?php

namespace App\Controller;

use App\Repository\SliderRepository;
use App\Service\bitrix24\BitrixService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/test")
 */
class TestController extends AbstractController
{
    /**
     * @Route("/", name="test-1")
     */
    public function test(BitrixService $bitrixService)
    {
        dd($bitrixService->findContactByEmail('faraday@nxt.ru'));
    }

    /**
     * @Route("/ar_fi", name="test-12")
     */
    public function arFi(SliderRepository $sr)
    {
        
        $sliders = $sr->findBy(['active' => true], ['priority' => 'DESC']);
        return $this->render('test.html.twig', ['sliders' => $sliders]);
    }
}
