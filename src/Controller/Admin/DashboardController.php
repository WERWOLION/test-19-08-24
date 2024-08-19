<?php

namespace App\Controller\Admin;

use App\Entity\BankBonusType;
use App\Entity\EmployeeRefLink;
use App\Entity\Log;
use App\Entity\Bank;
use App\Entity\News;
use App\Entity\Slider;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\Partner;
use App\Entity\BankMain;
use App\Entity\ChatRoom;
use App\Entity\Attachment;
use App\Entity\Calculated;
use App\Entity\ChatMessage;
use App\Entity\Sitesettings;
use App\Entity\Video;
use App\Form\NewsType;
use App\Service\OfferService;
use App\Repository\UserRepository;
use App\Repository\OfferRepository;
use App\Repository\PartnerRepository;
use App\Repository\AttachmentRepository;
use App\Controller\Admin\UserCrudController;
use App\Entity\MoneyRequest;
use App\Entity\Post;
use App\Entity\Transaction;
use App\Repository\MoneyRequestRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private UserRepository $userRepository,
        private AttachmentRepository $attachmentRepository,
        private OfferRepository $offerRepository,
    ){}


    /**
     * @Route("/admin", name="admin")
     */
    public function index(): Response
    {
        return $this->render('adminka/admin-dashboard.html.twig');
    }

    /**
     * @Route("/manage/partners", name="admin_partners")
     */
    public function partnersList(): Response
    {
        $partnersRep = $this->getDoctrine()->getRepository(Partner::class);
        $partners = $partnersRep->findAll();

        return $this->render('adminka/partners-list.html.twig', [
            'partners' => $partners,
        ]);
    }

    /**
     * @Route("/manage/referals", name="admin_referals")
     */
    public function referalsList(PartnerRepository $partnerRepository, OfferService $offerService): Response
    {
        $partners = $partnerRepository->createQueryBuilder('p')
            ->andWhere('SIZE(p.referals) > 0')
            ->getQuery()->getResult();
        foreach ($partners as $key => $partner) {
            $offerService->fillCalculatedToUser($partner->getUser());
        }
        return $this->render('adminka/referals-index.html.twig', [
            'partners' => $partners,
        ]);
    }

    /**
     * @Route("/manage/popup", name="admin_popup")
     */
    public function popupList(): Response
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $absolutePath = $projectDir . '/public_html/popup-settings.json';

        $popups = (array) json_decode(file_get_contents($absolutePath), true);
        
        return $this->render('adminka/popup-index.html.twig', [
            'popups' => $popups,
        ]);
    }

    /**
     * @Route("/manage/partner/{id}", name="admin_partner_show")
     */
    public function partnerShow(
        Partner $partner,
        MoneyRequestRepository $moneyRequestRepository,
        OfferService $offerService,
    ): Response
    {
        $partner->editURL = $this->adminUrlGenerator
        ->setController(UserCrudController::class)
        ->setAction(Action::EDIT)
        ->setEntityId($partner->getUser()->getId())
        ->generateUrl();
        $wallet = $partner->getUser()?->getWallet();
        $moneyRequests = [];
        if ($wallet) {
            $moneyRequests = $moneyRequestRepository->findBy([
                'status' => 20,
                'wallet' => $wallet,
            ], ['createdAt' => 'asc']);
        }
        $referals = $partner->getReferals();
        foreach ($referals as $referalPartner) {
            $offerService->fillCalculatedToUser($referalPartner->getUser());
        }
        return $this->render('adminka/partners-view.html.twig', [
            'partner' => $partner,
            'moneyRequests' => $moneyRequests,
            'referals' => $referals,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/img/logo-black.png" alt="">');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('backend.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Вернуться на сайт', 'fa fa-globe-europe', '/');
        yield MenuItem::linktoDashboard('Панель управления', 'fa fa-home');
        yield MenuItem::linkToCrud('Заявки', 'fas fa-file-invoice-dollar', Calculated::class);
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Реф ссылка для сотрудников', 'fas fa-link', EmployeeRefLink::class);
        yield MenuItem::linkToCrud('Документы', 'fas fa-file', Attachment::class);
        yield MenuItem::linkToCrud('Банки', 'fas fa-university', BankMain::class);
        yield MenuItem::linkToCrud('Типы комиссионных вознаграждений', 'fas fa fa-image', BankBonusType::class);
        yield MenuItem::linkToCrud('Уведомления', 'fas fa-bell', Log::class);
        yield MenuItem::linkToCrud('Регионы', 'fas fa-city', Town::class);
        yield MenuItem::linkToCrud('Чаты', 'fas fa-comment', ChatRoom::class);
        yield MenuItem::linkToCrud('Сообщения чата', 'fas fa-comment', ChatMessage::class);
        yield MenuItem::linkToCrud('Блоки контента', 'fas fa-paragraph', Sitesettings::class);
        yield MenuItem::linkToCrud('Страницы', 'fas fa-file-lines', Post::class);
        yield MenuItem::linkToRoute('Реферальная система', 'fas fa-id-card', 'admin_referals');
        yield MenuItem::linkToCrud('Начисления бонусов', 'fas fa-credit-card', Transaction::class);
        yield MenuItem::linkToCrud('Выплаты', 'fas fa-money-bill', MoneyRequest::class);
        yield MenuItem::linkToRoute('Всплывающие окна', 'fas fa fa-window-maximize', "admin_popup");
        yield MenuItem::linkToCrud('Обучающие видео', 'fas fa fa-video', Video::class);
        yield MenuItem::linkToCrud('Новостная лента', 'fas fa fa-rss', News::class);
        yield MenuItem::linkToCrud('Слайдер на главной', 'fas fa fa-image', Slider::class);
    }
}
