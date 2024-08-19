<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailSerivce;
use App\Service\PartnerService;
use App\Form\UpdatePasswordType;
use App\Form\RegistrationFormType;
use App\Service\UserCodeGenerator;
use Symfony\Component\Form\FormError;
use App\Service\bitrix24\BitrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class RegistrationController extends AbstractController
{

    // public function register(
    //     Request $request,
    //     UserPasswordHasherInterface $passwordHasher,
    //     UserCodeGenerator $codeGen,
    //     SessionInterface $session,
    //     EmailSerivce $emailService,
    //     BitrixService $bitrixService,
    // ): Response
    // {
    //     $user = new User();
    //     $form = $this->createForm(RegistrationFormType::class, $user);
    //     $form->handleRequest($request);

    //     $dublicates = false;
    //     if($form->isSubmitted()){
    //         $phone = $form->get('phone')->getData();
    //         $phoneDublicates = $bitrixService->contactTrueFind($phone);
    //         if($phoneDublicates && count($phoneDublicates)){
    //             $dublicates = true;
    //             // dump($phoneDublicates);
    //             $form->get('phone')->addError(new FormError("Этот телефон уже есть в системе."));
    //         }
    //     }

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $user->setPassword(
    //             $passwordHasher->hashPassword(
    //                 $user,
    //                 $form->get('plainPassword')->getData()
    //             )
    //         );

    //         //Создаём код подтверждения регистрации
    //         $authCode = $codeGen->getConfirmationCode();
    //         $authLink = $request->server->get('REQUEST_SCHEME') . "://" .  $request->server->get('SERVER_NAME') . '/activate/' . $authCode;

    //         //посылаем письма с активацией
    //         try {
    //             $emailService->sendRegEmail($user->getEmail(), $authLink);
    //         } catch (\Throwable $th) {
    //             // dd($th);
    //             $form->get('email')->addError(new FormError('Не удалось подтвердить этот Email'));
    //             $this->addFlash(
    //                 'error',
    //                 'Мы не смогли отправить письмо на email: ' . $user->getEmail() . '. Убедитесь, что он существует, или попробуйте использовать другой email-адрес'
    //             );
    //             return $this->render('security/register.html.twig', [
    //                 'registrationForm' => $form->createView(),
    //             ]);
    //         }

    //         $user->setRoles(["ROLE_USER"]);
    //         $user->setIsEmailConfirm(false);
    //         $user->setEmailCode($authCode);
    //         $entityManager = $this->getDoctrine()->getManager();
    //         $entityManager->persist($user);
    //         $entityManager->flush();
    //         $session->set('email-text', $user->getEmail());
    //         return $this->redirectToRoute('registration_success');
    //     }

    //     return $this->render('security/register.html.twig', [
    //         'registrationForm' => $form->createView(),
    //         'idBitrixDouble' => $dublicates,
    //     ]);
    // }


    // public function resendEmail(
    //     User $user,
    //     Request $request,
    //     SessionInterface $session,
    //     EmailSerivce $emailService
    // )
    // {
    //     $authLink = $request->server->get('REQUEST_SCHEME') . "://" .  $request->server->get('SERVER_NAME') . '/activate/' . $user->getEmailCode();
    //     $emailService->sendRegEmail($user->getEmail(), $authLink);
    //     $session->set('email-text', $user->getEmail());

    //     return $this->redirectToRoute('registration_success');
    // }



    // public function RegistrationSuccess(Request $request, SessionInterface $session): Response
    // {
    //     return $this->render('security/register-success.html.twig', [
    //         'emailName' => $session->get('email-text'),
    //     ]);
    // }


    // public function ActivateUser(
    //     string $code,
    //     Request $request,
    //     EventDispatcherInterface $dispatcher,
    //     PartnerService $partnerService,
    //     EmailSerivce $emailService
    // )
    // {
    //     //Находим пользователя с таким кодом активации Email (поле - emailCode)
    //     $user = $this->getDoctrine()
    //         ->getRepository(User::class)
    //         ->findOneBy(['emailCode' => $code]);

    //     //Если такого нет - то ошибка 404
    //     if ($user === null) {
    //         return $this->render('security/email-codeexp.html.twig', []);
    //         // return new Response('404');
    //     }

    //     //Если есть, удаляем очищаем токен и активируем пользователя
    //     $user->setEmailCode("");
    //     $user->setIsEmailConfirm(true);

    //     //Обновляем пользователя в БД
    //     $partnerService->PartnerInitNew($user);

    //     $this->addFlash(
    //         'notice',
    //         'Ваш Email адрес: ' . $user->getEmail() . ' подтвержден успешно.'
    //     );

    //     //Сразу авторизовываем пользователя, если код подошёл и пользователь активирован
    //     // $guardHandler->authenticateUserAndHandleSuccess(
    //     //     $user,
    //     //     $request,
    //     //     $authenticator,
    //     //     'main'
    //     // );
    //     $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
    //     $this->get("security.token_storage")->setToken($token);
    //     $event = new SecurityEvents($request);
    //     $dispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);

    //     $emailService->sendConfirmEmail($user->getEmail(), $user);

    //     return $this->render('security/email-success.html.twig', []);
    // }



    // /**
    //  * @Route("/activate/manager/{id}", name="activate_manager")
    //  */
    // public function ActivateManager(
    //     User $user,
    //     PartnerService $partnerService,
    //     EmailSerivce $emailService)
    // {
    //     return new Response('Выключено');
    //     //Обновляем пользователя в БД
    //     $newUser = $partnerService->activateManager($user, 123456);
    //     $this->getDoctrine()->getManager()->flush();
    //     $emailService->sendConfirmEmail($user->getEmail(), $user);
    // }





    /**
     * @Route("/password_restore", name="password_restore")
     */
    public function restorePassword(
        Request $request,
        UserCodeGenerator $codeGen,
        EmailSerivce $emailService,
        EntityManagerInterface $em
    ){
        if($request->request->get('email')){
            //получаем данные из формы
            $submittedToken = $request->request->get('_csrf_token');
            $email = $request->request->filter('email', null, \FILTER_VALIDATE_EMAIL);

            //пробуем получить пользователя из email
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($this->isCsrfTokenValid('reset-password', $submittedToken) && $email && $user) {

                //форма пришла, токен верный
                $error = null;

                //устнавливаем поле с кодом смены пароля.
                $authCode = $codeGen->getConfirmationCode();
                $user->setPassCode($authCode);
                $em->persist($user);
                $em->flush();

                //ссылка для смены пароля
                $authLink = $request->server->get('REQUEST_SCHEME') . "://" .  $request->server->get('SERVER_NAME') . '/passupdate/' . $authCode;
                $emailService->sendPassChangeEmail($user->getEmail(), $authLink);

                $this->addFlash(
                    'notice',
                    'Проверьте свой Email: ' . $user->getEmail() . '. На него выслано письмо для смены пароля'
                );
                return $this->redirectToRoute('password_sended');
            } else {
                //либо пароль неправильный, либо токен
                $error = true;
            }

        } else {

            //форма не пришла, нужно её просто отобразить
            $error = null;
        }

        return $this->render('security/password_restore.html.twig', [
            'error' => $error,
        ]);
    }



    /**
     * @Route("/password_sended", name="password_sended")
     */
    public function PasswordSended()
    {
        return $this->render('security/password_success.html.twig', []);
    }



    /**
     * @Route("/passupdate/{code}", name="password_update")
     */
    public function updatePassword(
        string $code,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ){
        $user = $em->getRepository(User::class)->findOneBy(['passCode' => $code]);

        //Если такого нет - то ошибка 404
        if ($user === null) {
            return new Response('404');
        }
        $form = $this->createForm(UpdatePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setPassCode(null);
            $em->persist($user);
            $em->flush();
            $this->addFlash(
                'notice',
                'Пароль успешно изменен.'
            );
            return $this->redirectToRoute('lk');
        }
        return $this->render('security/password_update.html.twig', [
            'password_form' => $form->createView(),
        ]);
    }



    /**
     * @Route("/nonemail/", name="email_not_active")
     * страница для пользователя, если Email не подтвержден
     */
    public function EmailNotActive(Request $request): Response
    {
        if($this->isGranted('isUserEmailConfirmed')){
            return $this->redirectToRoute('lk');
        }
        return $this->render('security/noemail.html.twig', []);
    }
}
