<?php
namespace App\Service\UserServices;

use App\Entity\User;
use App\Entity\PreUser;
use App\Repository\PartnerRepository;
use App\Service\Logs\LogService;
use App\Service\UserCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class RegistrationService
{
    public function __construct(
        private Security $security,
        private UserCodeGenerator $userCodeGenerator,
        private RegistrationBitrixService $registrationBitrixService,
        private EntityManagerInterface $em,
        private RequestStack $request,
        private MailerInterface $mailer,
        private LogService $logService,
        private PartnerRepository $partnerRepository,
    ) {}

    public function makeRegistrationLead(PreUser $preUser) : PreUser
    {
        $code = $this->userCodeGenerator->getConfirmationCode();
        $tryCount = $preUser->getTryCount() ? $preUser->getTryCount() : 0;
        $preUser->setAcceptCode($code);
        $preUser->setTryCount($tryCount + 1);
        if($preUser->getBitrixLeadId()){
        } else {
            $leadId = $this->registrationBitrixService->makeRegistrationLead($preUser);
            $preUser->setBitrixLeadId($leadId);
        }
        return $preUser;
    }

    public function updateRegistrationLead(PreUser $preUser, string $newEmail) : PreUser
    {
        if (!$preUser->getBitrixLeadId()) throw new \Error('Ошибка. Лид не установлен!');
        $preUser->setTryCount($preUser->getTryCount() + 1);
        $preUser->setAcceptCode($this->userCodeGenerator->getConfirmationCode());
        try {
            $this->registrationBitrixService->updateLeadComment($preUser, $newEmail);
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
        $preUser->setEmail($newEmail);
        return $preUser;
    }

    public function sendFirstEmail(PreUser $preUser)
    {
        $request = $this->request->getCurrentRequest();
        $confirmLink = $request->server->get('REQUEST_SCHEME') . "://" .  $request->server->get('SERVER_NAME') . '/sec/act/' . $preUser->getAcceptCode();
        $mailerObj = (new TemplatedEmail())
        ->from('reg@ipolife.ru')
        ->to($preUser->getEmail())
        ->subject('Ipoteka.life. Подтверждение регистрации.')
        ->htmlTemplate('email/prereg_new.html.twig')
        ->context([
            'regLink' => $confirmLink,
            'emailSelf' => $preUser->getEmail(),
        ]);
        $this->mailer->send($mailerObj);
    }

    public function confirmReg(PreUser $preUser, User $user)
    {
        $preUser->setAcceptCode(null);
        $preUser->setIsConfirm(true);
        $other = $preUser->getOther();
        $other['user'] = $user->getId();
        $other['activateAt'] = new \DateTimeImmutable();
        $preUser->setOther($other);
        $dealId = $this->registrationBitrixService->makeRegDeal($user, $preUser->getEmployeeRefLink());
        $this->registrationBitrixService->chanheLeadStatus($preUser->getBitrixLeadId(), 'CONVERTED');
        $partner = $user->getPartner();
        $partner->setBitrixRegDealID($dealId);
        $log = $this->logService->succesReg($user);
        $this->em->persist($log);
        $this->em->persist($preUser);
        $this->em->persist($partner);
        $this->em->flush();
    }
}
