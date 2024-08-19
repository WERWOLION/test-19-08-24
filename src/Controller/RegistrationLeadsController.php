<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\PreUser;
use App\Repository\EmployeeRefLinkRepository;
use App\Service\EmailSerivce;
use App\Service\PartnerService;
use App\Form\UpdatePasswordType;
use App\Service\Logs\LogService;
use App\Form\RegistrationFormType;
use App\Form\RegistrationLeadType;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\UserCodeGenerator;
use App\Repository\PreUserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use App\Service\bitrix24\BitrixService;
use App\Service\ReferalsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UserServices\RegistrationService;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationLeadsController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserCodeGenerator $codeGen,
        private UserRepository $userRepository,
        private SessionInterface $session,
        private EmailSerivce $emailService,
        private BitrixService $bitrixService,
        private RegistrationService $registrationService,
        private PreUserRepository $preUserRepository,
        private LogService $logService,
        private SessionInterface $sessionInterface,
    ) {
    }

    /**
     * @Route("/register", name="app_register_lead")
     * @Route("/sec/rf{refid}", name="app_register_referal")
     */
    public function register_lead(
        Request $request,
        ?string $refid,
        UserRepository $userRepository,
        PreUserRepository $preUserRepository,
        HttpClientInterface $client,
        PartnerRepository $partnerRepository,
        EmployeeRefLinkRepository $employeeRefLinkRepository
    ): Response {
        $referalId = null;
        $employeeRefId = null;
        $intRefId = intval($refid);
        if ($intRefId) {
            $partner = $partnerRepository->find($intRefId);
            if ($partner) {
                $referalId = $partner->getId();
            }
        }
        $preUser = new PreUser();
        $employeeRefStr = $request->get('ref_hash');
        if (!is_null($employeeRefStr)) {
            $employeeRef = $employeeRefLinkRepository->findOneBy(['hash' => $employeeRefStr]);
            $preUser->setEmployeeRefLink($employeeRef);
        }
        if (!is_null($referalId)) {
            $partnerPreUser = $preUserRepository->findByOtherUserid($partner->getUser()->getId());
            if ($partnerPreUser->getEmployeeRefLink()) {
                $preUser->setEmployeeRefLink($partnerPreUser->getEmployeeRefLink());
            }
        }

        $form = $this->createForm(RegistrationLeadType::class, $preUser);
        $form->handleRequest($request);
        $dublicates = false;

        //Проверка на имеющийся номер телефона в битрикс24
        if ($form->isSubmitted()) {
            $phone = $form->get('phone')->getData();
            $email = $form->get('email')->getData();
            
            if ($_SERVER['APP_ENV'] === 'prod') {
                $capchaToken = $form->get('token')->getData();
                if (!$capchaToken) $form->addError(new FormError("Ошибка. Подозрение на спам"));
            }
            if (!str_starts_with($phone, '+7')) {
                $form->get('phone')->addError(new FormError("Ошибка. Телефон должен начинаться с +7"));
            }

            if ($_SERVER['APP_ENV'] === 'prod') {
                $capchaRequest = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret' => '6LcZ8CcpAAAAABNEa5wk6y5qrSTMe-A5PPHM6O8m',
                        'response' => $capchaToken,
                    ],
                ]);
                //6LdY6qQZAAAAAMeAPRvOTnG5GWIl3R9yDMdHm8IO
                $capchaResult = $capchaRequest->toArray();
                if (!isset($capchaResult['success']) || !$capchaResult['success']) {
                    $form->addError(new FormError("Ошибка reCAPTCHA. Подозрение на спам!"));
                }
            }
            /* отключаем проверку телефона в Битрикс
            $phoneDublicates = $this->bitrixService->contactTrueFind($phone);
            if ($phoneDublicates && count($phoneDublicates)) {
                $dublicates = true;
                $form->get('phone')->addError(new FormError("Этот телефон уже есть в системе"));
            }
            */
            $emailDublicates = $userRepository->findOneBy([
                'email' => $email,
            ]);
            if ($emailDublicates) {
                $form->get('email')->addError(new FormError("Этот email уже зарегистрирован"));
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {

            $existsLeads = $this->preUserRepository->findByPhone($phone);
            if (is_array($existsLeads) && count($existsLeads)) {
                $preUser = $this->registrationService->updateRegistrationLead($existsLeads[0], $email);
            } else {
                $preUser = $this->registrationService->makeRegistrationLead($preUser);
            }

            if ($referalId) {
                $preUser->setOther(['referalId' => $referalId]);
            }

            $this->em->persist($preUser);
            $this->em->flush();
            $this->logService->preRegisterLog($preUser, true);
            $this->registrationService->sendFirstEmail($preUser);
            $this->session->set('email-text', $preUser->getEmail());
            return $this->redirectToRoute('app_register_lead_success');
        }
        return $this->render('registration/leadform.html.twig', [
            'leadForm' => $form->createView(),
            'idBitrixDouble' => $dublicates,
            'referalId' => $referalId,
        ]);
    }

    /**
     * @Route("/register_landing", name="app_register_lead_landing")
     *
     */
    public function register_lead_landing(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $preUser = new PreUser();
        $phone = $request->request->get('phone') ?? $request->request->get('Phone');
        $email = $request->request->get('email');
        if ($phone == null || $email == null) {
            $json = ['success' => false];
            $json['error'] = 'Не заполнено поле ' . ($phone == null ? 'телефон' : 'емейл');
            return $this->json($json);
        }
        $phone = str_replace(["-", " ", "(", ")"], "", $phone);
        $email = $request->request->get('email');
        $preUser->setEmail($email);
        $preUser->setPhone($phone);

        if (!str_starts_with($phone, '+7')) {
            return $this->json(['success' => false, 'error'=> 'Ошибка. Телефон должен начинаться с +7']);
        }
        $emailDublicates = $userRepository->findOneBy([
            'email' => $email,
        ]);
        if ($emailDublicates) {
            return $this->json(['success' => false, 'error' => 'Этот email уже зарегистрирован']);
        }

        $existsLeads = $this->preUserRepository->findByPhone($phone);
        if (is_array($existsLeads) && count($existsLeads)) {
            $preUser = $this->registrationService->updateRegistrationLead($existsLeads[0], $email);
        } else {
            $preUser = $this->registrationService->makeRegistrationLead($preUser);
        }

        $this->em->persist($preUser);
        $this->em->flush();
        $this->logService->preRegisterLog($preUser, true);
        $this->registrationService->sendFirstEmail($preUser);
        return $this->json(['success' => true]);
    }

    /**
     * @Route("/sec/regsuccess/", name="app_register_lead_success")
     */
    public function regInfo()
    {
        return $this->render('registration/textbox.html.twig', [
            'title' => "Подтвердите ваш Email",
            'content' => "<p>На ваш Email - <b>{$this->session->get('email-text')}</b> отправлено письмо со ссылкой на подтверждение регистрации.</p> <p>Перейдите по ссылке, чтобы продолжить регистрацию в системе Ipoteka.life</p>",
        ]);
    }


    /**
     * @Route("/sec/act/{acceptCode}", name="activate_and_register")
     */
    public function activateAndRegister(
        string $acceptCode,
        Request $request,
        PreUserRepository $preUserRepository,
        PartnerRepository $partnerRepository,
        EventDispatcherInterface $dispatcher,
        PartnerService $partnerService,
        ReferalsService $referalsService
    ) {
        $preUser = $preUserRepository->findOneBy([
            'acceptCode' => $acceptCode,
        ]);
        if (!$preUser) {
            return $this->render('registration/textbox.html.twig', [
                'title' => "Код подтверждения не верен",
                'content' => "<p>Продолжить регистрацию невозможно. Код подтверждения неверен.</p><p>Попробуйте <a href='/sec/register/' class='bluelink'>зарегистрироваться заново</a></p>",
            ]);
        }

        $existUser = $this->userRepository->findOneBy([
            'email' => $preUser->getEmail(),
        ]);

        if ($existUser) {
            return $this->render('registration/textbox.html.twig', [
                'title' => "Email {$preUser->getEmail()} уже зарегистрирован",
                'content' => "<p>Продолжить регистрацию невозможно. <label for='bitrixContactUS' class='bluelink'>Напишите нам</label>, мы разберемся в проблеме.</p>",
            ]);
        }

        $user = new User();
        $user->setEmail($preUser->getEmail());
        $user->setPhone($preUser->getPhone());
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'email' => $preUser->getEmail(),
            'phone' => $preUser->getPhone()
        ]);

        $other = $preUser->getOther();
        $referal = null;
        if (isset($other['referalId']) && $other['referalId']) {
            $referalPartner = $partnerRepository->find($other['referalId']);
            if ($referalPartner) $referal = $referalPartner;
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $user->setIsEmailConfirm(true);

            //Обновляем пользователя в БД, создаём пустого партнера и чат техподдержки
            $partnerService->PartnerInitNew($user, $_POST["purposeReg"], $preUser->getEmployeeRefLink());

            /**
             * Создаём сделку Битрикс24 в разделе "Регистрация", и добавляем ID сделки
             * в поле bitrixRegDealID партнера. Деактивируем PreUser, меняем статус лида у PreUser.
             */
            $this->registrationService->confirmReg($preUser, $user);

            /**
             * Заполняем поле реферала, создаём предварительную транзакцию 10000 руб за реферала
             */
            if ($referal) {
                $referalsService->createReferal($referal, $user->getPartner());
                $this->bitrixService->createReferalDeal($referal, $user->getPartner(), 10000);
            }

            $this->addFlash('notice', 'Вы успешно зарегистрированы в сервисе');

            //Сразу авторизуем пользователя
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->container->get("security.token_storage")->setToken($token);
            $event = new SecurityEvents($request);
            $dispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);

            // $emailService->sendConfirmEmail($user->getEmail(), $user);

            return $this->render('registration/textbox.html.twig', [
                'title' => "Регистрация успешно завершена",
                'content' => "<p>Вы успешно зарегистрированы и авторизированы в сервисе Ipoteka.life. Чтобы подать заявку, перейдите в личный кабинет.</p>",
            ]);
        }

        return $this->renderForm('registration/leadform.html.twig', [
            'leadForm' => $form,
            'idBitrixDouble' => false,
            'isRegForm' => true,
            'referalId' => $referal?->getId(),
            'isPurposeReg' => 123123,
        ]);
    }
}
