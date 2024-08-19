<?php
namespace App\Service;

use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;



class EmailSerivce{

    private $mailer;

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }


    public function sendRegEmail($email, $activateLink)
    {
        $mailerObj = (new TemplatedEmail())
            ->from('reg@ipolife.ru')
            ->to($email)
            ->subject('Ipoteka.life. Подтверждение регистрации.')
            ->htmlTemplate('email/reg.html.twig')
            ->context([
                'emailAddres' => $email,
                'regLink' => $activateLink,
            ]);
        $this->mailer->send($mailerObj);
    }

    
    public function sendConfirmEmail($email, $user)
    {
        $mailerObj = (new TemplatedEmail())
            ->from('reg@ipolife.ru')
            ->to($email)
            ->subject('Ipoteka.life. Регистрация подтверждена.')
            ->htmlTemplate('email/reg_success.html.twig')
            ->context([
                'emailAddres' => $email,
                'user' => $user,
            ]);
        $this->mailer->send($mailerObj);
    }



    public function sendPassChangeEmail($email, $activateLink)
    {
        $mailerObj = (new TemplatedEmail())
            ->from('reg@ipolife.ru')
            ->to($email)
            ->subject('Ipoteka.life. Смена пароля')
            ->htmlTemplate('email/password_new.html.twig')
            ->context([
                'emailAddres' => $email,
                'regLink' => $activateLink,
            ]);
        $this->mailer->send($mailerObj);
    }

}