<?php
namespace App\Service\Logs;

use App\Entity\Calculated;
use App\Entity\Log;
use App\Entity\PreUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class LogService
{

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {}

    public function addSysLog(string $content, ?bool $isFlush = false)
    {
        $log = new Log();
        $log->setContent($content);
        $log->setTitle('Сист. лог');
        if($isFlush){
            $this->em->persist($log);
            $this->em->flush();
        }
        return $log;
    }

    public function preRegisterLog(PreUser $preUser, ?bool $isFlush = false) : Log
    {
        $log = new Log();
        $log->setEntityType('PreUser');
        $log->setEntityId($preUser->getId());
        $log->setTitle("Создан лид регистрации: {$preUser->getBitrixLeadId()}");
        $log->setContent(
            "Зарегистрирован пользователь: email: {$preUser->getEmail()}, тел: {$preUser->getPhone()}, попытка регистрации: {$preUser->getTryCount()}"
        );
        if($isFlush){
            $this->em->persist($log);
            $this->em->flush();
        }
        return $log;
    }

    public function succesReg(User $user, ?bool $isFlush = false) : Log
    {
        $log = new Log();
        $log->setEntityType('User');
        $log->setEntityId($user->getId());
        $log->setTitle("Подтвержденная регистрация: {$user->getEmail()}");
        $log->setContent(
            "Подтверждена регистрация: email: {$user->getEmail()}, тел: {$user->getPhone()}, партнер B24 {$user->getPartner()->getBitrixContactID()}, сделка регистрации B24 {$user->getPartner()->getBitrixRegDealID()}"
        );
        if($isFlush){
            $this->em->persist($log);
            $this->em->flush();
        }
        return $log;
    }

    public function calcLog(int $calcId, string $title, string $content, ?bool $isFlush = false) : Log
    {
        $log = new Log();
        $log->setEntityType('Calculated');
        $log->setEntityId($calcId);
        $log->setTitle($title);
        $log->setContent($content);
        if($isFlush){
            $this->em->persist($log);
            $this->em->flush();
        }
        return $log;
    }
}
