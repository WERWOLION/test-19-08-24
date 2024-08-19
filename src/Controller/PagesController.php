<?php

namespace App\Controller;

use App\Entity\BankNum;
use App\Entity\News;
use App\Entity\Partner;
use App\Entity\Post;
use App\Entity\Sitesettings;
use App\Entity\Video;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use App\Service\OfferService;
use App\Service\PartnerService;
use App\Service\Logs\LogService;
use App\Repository\BankMainRepository;
use App\Repository\BankOptionRepository;
use App\Service\bitrix24\BitrixService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Lock\LockFactory;
use App\Repository\CalculatedRepository;
use App\Repository\SliderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PagesController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CalculatedRepository $calculatedRepository,
        private LogService $logService,
    ) {}

    /**
     * @Route("/", name="lk")
     */
    public function index(BitrixService $bitrixService, Request $request, SliderRepository $sr): Response
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')){
            return $this->redirectToRoute('app_login');
        }
        if(!$this->isGranted('isUserEmailConfirmed')){
            return $this->redirectToRoute('email_not_active');
        }
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        $myBittrixID = intval($currentUser->getPartner()?->getBitrixContactID());
        if($myBittrixID && !$bitrixService->contactGet($myBittrixID)){
            $this->addFlash(
                'error',
                'Ошибка обработки вашего аккаунта партнера (' . $currentUser->getPartner()?->getBitrixContactID() . '). Обратитесь в техподдержку'
            );
        }
        $sliders = $sr->findBy(['active' => true], ['priority' => 'ASC']);
        return $this->render('pages/frontpage.html.twig', [
            'sliders' => $sliders
        ]);
    }


    /**
     * @Route("/services", name="services")
     */
    public function services(): Response
    {
        if(!$this->isGranted('isUserEmailConfirmed')){
            return $this->redirectToRoute('email_not_active');
        }
        return $this->render('pages/services.html.twig', [
        ]);
    }


    /**
     * @Route("/page/{slug}", name="simple_page")
     */
    public function post(Post $post): Response
    {
        if(!$post->isIsAnon() && !$this->isGranted('IS_AUTHENTICATED_REMEMBERED')){
            return $this->redirectToRoute('app_login');
        }
        if(!$post->isIsPublish()){
            throw $this->createAccessDeniedException('Страница не опубликована');
        }
        return $this->render('pages/simple.html.twig', [
            'page' => $post,
        ]);
    }

    /**
     * @Route("/doc/{label}", name="simplepage")
     */
    public function blockPage(Sitesettings $sitesettings): Response
    {
        $label = $sitesettings->getLabel();
        if(!str_starts_with($label, 'page') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }
        return $this->render('pages/simple.html.twig', [
            'page' => $sitesettings,
        ]);
    }

    /**
     * @Route("/table", name="table")
     */
    public function table(): Response
    {
        $tableUrl = "https://docs.google.com/spreadsheets/d/1f0k9fAYdD17ih5ehrD18cY_rdafVby_a3pArrPaZDpM/edit#gid=1142340928&amp;amp;rm=minimal&amp;amp;single=true&amp;amp;widget=false&amp;amp;headers=false";
        return $this->render('pages/table.html.twig', [
            'url' => $tableUrl,
        ]);
    }

    /**
     * @Route("/anon/bitrix_hook", name="bitrix_hook")
     */
    public function bitrix_hook(
        BitrixService $bitrixService,
        LoggerInterface $testLogger,
        OfferService $offerService,
        Request $request,
        PartnerService $partnerService,
    ){

        $eventArray = $request->request->all();

        if($eventArray['auth']['application_token'] !== "usyt5mfi9v276hpqok16lal398ii5gow"){
            $testLogger->info("Неверный токен: " . $eventArray['event']['auth']['application_token']);
            throw $this->createNotFoundException("Токен неверный");
        }

        //Изменение заявки
        if($eventArray['event'] === "ONCRMDEALUPDATE"){
            $result = isset($eventArray['data']['FIELDS']['ID']) ? $eventArray['data']['FIELDS']['ID'] : null;
            if($result){
                $calc = $this->calculatedRepository->findOneBy(
                    ['bitrixID' => intval($result)]
                );
                if(!$calc) throw $this->createNotFoundException("Заявки хука не найдено: {$result}");

                $store = new SemaphoreStore();
                $factory = new LockFactory($store);
                $lock = $factory->createLock('cashloc_' . $result);
                if($lock->acquire()){
                    $bitrixService->dealDownload($calc);
                    $lock->release();
                }
            } else {
                $this->logService->addSysLog('Ошибка Веб-хука. Хук пришёл без ID сделки', true);
            }
        }

        //Удаление заявки
        if($eventArray['event'] === "ONCRMDEALDELETE"){
            $result = isset($eventArray['data']['FIELDS']['ID']) ? $eventArray['data']['FIELDS']['ID'] : null;
            if($result){
                $calc = $this->calculatedRepository->findOneBy(
                    ['bitrixID' => intval($result)]
                );
                if(!$calc) throw $this->createNotFoundException("Завки не найдено");

                $store = new SemaphoreStore();
                $factory = new LockFactory($store);
                $lock = $factory->createLock('delloc_' . $result);
                if($lock->acquire()){
                    $offerService->calcDelete($calc);
                    $lock->release();
                }
                $this->logService->calcLog(
                    $calc->getId(),
                    "Сделка удалена {$calc->getId()}",
                    "Сделка удалена {$calc->getId()} из хука",
                    true
                );
            } else {
                $this->logService->addSysLog('Ошибка Веб-хука. Хук пришёл без ID сделки', true);
            }
        }

        //Изменение контакта
        if($eventArray['event'] === "ONCRMCONTACTUPDATE"){
            $result = isset($eventArray['data']['FIELDS']['ID']) ? $eventArray['data']['FIELDS']['ID'] : null;
            if($result){

                $store = new SemaphoreStore();
                $factory = new LockFactory($store);
                $lock = $factory->createLock('contactloc_' . $result);
                if($lock->acquire()){
                    $partnerRep = $this->em->getRepository(Partner::class);
                    $partner = $partnerRep->findOneBy(['bitrixContactID' => $result]);
                    if($partner && $partner->getUser()){
                        $partnerService->partnerUpdateAssigned($partner->getUser());
                        // $this->logService->addSysLog(json_encode($eventArray), true);
                    } else {}
                    $lock->release();
                }
            } else {
                $this->logService->addSysLog('Ошибка Веб-хука. Хук пришёл без ID партнера', true);
            }
        }
        return new Response('Это для хука');
    }



    /**
     * @Route("/tester", name="tester")
     */
    public function tester(
        BankOptionRepository $bankOptionRepository,
    )
    {
        dd("Отключено");
        $options = $bankOptionRepository->findAll();
        foreach ($options as $key => $opt) {
            if(!$opt->getProcIt()) {
                $opt->setProcIt(new BankNum());
            }
            if(!$opt->getFirstIt()) {
                $opt->setFirstIt(new BankNum());
            }
            $this->em->persist($opt);
        }
        $this->em->flush();
    }

    /**
     * @Route("/videos", name="videos")
     */
    public function videos(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        if(!$this->isGranted('isUserEmailConfirmed')){
            return $this->redirectToRoute('email_not_active');
        }
        $repository = $this->getDoctrine()->getRepository(Video::class);
        $query = $repository->getQuerySearch();
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            5 /*limit per page*/
        );
        $video = $repository->findOneBy(['selected' => true], ['created_at' => 'DESC']);
        return $this->render('pages/videos.html.twig', ['pagination' => $pagination, 'selectedvideo' => $video]);
    }

    /**
     * @Route("/news", name="news")
     */
    public function news(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response
    {
        if(!$this->isGranted('isUserEmailConfirmed')){
            return $this->redirectToRoute('email_not_active');
        }
        $repository = $this->getDoctrine()->getRepository(News::class);
        $query = $repository->getQuerySearch();
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            3 /*limit per page*/
        );
        //$news = $repository->findBy([], ['created_at' => 'DESC']);
        return $this->render('pages/news.html.twig', ['pagination' => $pagination]);
    }

    /**
     * @Route("/inssmart", name="app_inssmart")
     */
    public function inssmart(): Response
    {
        return $this->render('pages/inssmart.html.twig');
    }

    /**
     * @Route("/inssmart_auth", name="app_inssmart_auth")
     */
    public function inssmartAuth(HttpClientInterface $client): Response
    {
        $currentUser = $this->getUser();
        $jsonData = [
            "privateKey" => "8060b620-c238-556d-8792-afc6e2a037c8",
            "appGuid" => "d2a79688-bcbf-576e-a3a0-7d6d01d460ae",
            "description" => $currentUser->getFio(),
            "partnerId" => $currentUser->getId(),
//               "parentPartnerId"=> "1"
        ];
        $response = $client->request('POST', 'https://api.inssmart.ru/v1/widget/clients/token', [
            'headers' => [
                'cache-control: no cache',
                'Content-Type: application/json',
            ],
            'body' => json_encode($jsonData),
        ]);
        return $this->json(json_decode($response->getContent()));
    }

}
