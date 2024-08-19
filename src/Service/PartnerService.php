<?php

namespace App\Service;

use App\Entity\EmployeeRefLink;
use App\Entity\User;
use App\Entity\Wallet;
use App\Entity\Partner;
use App\Entity\ChatRoom;
use App\Repository\BankMainRepository;
use Dadata\DadataClient;
use App\Repository\BankRepository;
use App\Repository\UserRepository;
use App\Service\bitrix24\BitrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;




class PartnerService
{
    private $em;
    private $bitrixService;
    private $params;
    private $bankRep;
    private $userRep;


    public function __construct(
        EntityManagerInterface $em,
        BitrixService $bitrixService,
        ContainerBagInterface $params,
        BankRepository $bankRep,
        UserRepository $userRep,
        private BankMainRepository $bankMainRepository,
    ) {
        $this->bitrixService = $bitrixService;
        $this->em = $em;
        $this->params = $params;
        $this->bankRep = $bankRep;
        $this->userRep = $userRep;
    }

    public function isBitrixActive(): bool
    {
        return boolval($this->params->get('isBitrixActive'));
    }

    /**
     * Метод создает пустого партнера к пользователю.
     * Создаёт и прикрепляет кошелек
     */
    public function PartnerInitNew(User $newUser, string $purposeReg, ?EmployeeRefLink $employeeRefLink): User
    {
        if ($newUser->getPartner()) {
            throw new \Exception('У пользователя уже создан партнер, ID: ' . $newUser->getPartner()->getId());
        }
        $newPartner = new Partner();
        $newWallet = new Wallet();
        $newUser->setRoles(["ROLE_USER"]);
        $newUser->setWallet($newWallet);
        $this->em->persist($newUser);
        $this->em->flush();
        $newPartner->setUser($newUser);
        $this->bitrixService->contactAddPartner($newPartner, $purposeReg, $employeeRefLink);
        $this->partnerUpdateAssigned($newUser);
        $genericChat = $this->parnerCreateChat($newPartner);
        $this->em->persist($newUser);
        $this->em->persist($newPartner);
        $this->em->persist($genericChat);
        $this->em->flush();
        return $newUser;
    }

    /**
     * Метод создает менеджера из пользователя
     */
    public function activateManager(User $newUser, int $bitrixManagerId): User
    {
        if ($newUser->getPartner()) {
            throw new \Exception('У пользователя уже создан партнер, ID: ' . $newUser->getPartner()->getId());
        }
        $newPartner = new Partner();
        $newWallet = new Wallet();
        $newUser->setEmailCode("");
        $newUser->setIsEmailConfirm(true);
        $newUser->setRoles(["ROLE_USER", "ROLE_MANAGER"]);
        $newUser->setWallet($newWallet);
        $newPartner->setUser($newUser);
        $this->em->persist($newUser);
        $this->em->persist($newPartner);
        $newUser->setBitrixManagerID($bitrixManagerId);
        return $newUser;
    }


    public function partnerGetStartData(User $user, $type): Partner
    {
        if (!$user->getPartner()) {
            throw new \Exception("У пользователя " . $user->getId() . " ещё не создан партнер");
        }
        if (!in_array($type, [1, 2, 3, 4])) {
            throw new \Exception("Передан неверный тип партнера");
        }
        // $fullName = \trim($user->getLastname() . " " . $user->getFirstname() . " " . $user->getMiddlename());

        $partner = $user->getPartner();
        // $partner->setFullname($fullName);
        $partner->setType($type);
        return $partner;
    }



    public function partnerUpdateAssigned(User $user)
    {
        if (!$this->isBitrixActive()) return;
        $adminArray = $this->bitrixService->getAssignedAdmin($user);
        $partner = $user->getPartner();
        $other = $partner->getOther();
        $other['assisit_name'] = $adminArray['LAST_NAME'] . " " . $adminArray['NAME'] . " " . ($adminArray['SECOND_NAME'] ?? '');
        $other['assisit_phone'] = $adminArray['PERSONAL_MOBILE'] ?? '';
        if (isset($adminArray['EMAIL'])) {
            $other['assisit_email'] = $adminArray['EMAIL'];
        }
        $manager = $this->userRep->findOneBy(['bitrixManagerID' => $adminArray['ID']]);

        if ($manager) {
            $user->setMyManager($manager);
        } else {
            // $admin = $this->userRep->find(11);
            // $user->setMyManager($admin);
        }
        $partner->setOther($other);
        $this->em->persist($user);
        $this->em->persist($partner);
        $this->em->flush();
    }

    /**
     * Проверяет введенный ИНН на наличие верных кодов ОКВЭД через сервис Dadata
     */
    public function parnerTestOkved(Partner $partner): bool
    {
        $inn = $partner->getInn();
        $type = $partner->getType();
        if (!$type) {
            throw new \Exception('У парнера не установлен тип');
        }
        if (!in_array($type, [3, 4])) {
            return true;
        }
        $token = "7c36a2ae6dac684b74581084c4c87525013049ca";
        $dadata = new DadataClient($token, null);
        $result = $dadata->findById("party", $inn, 1);
        $okved = $result[0]['data']['okved'];
        $trustedOkved = ["68.31", "82.99", "66.19.1", "66.19"];
        if ($okved && in_array($okved, $trustedOkved)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Создаёт общий чат техподдержки для партнера
     */
    public function parnerCreateChat(Partner $partner): ChatRoom
    {
        $partnerChat = new ChatRoom();
        $partnerChat->setIsGenericDialog(true);
        $partnerChat->setTitle('Техническая поддержка');
        $partnerChat->setUser($partner->getUser());
        $partnerChat->addViewedByUser($partner->getUser());
        if ($partner->getUser()->getMyManager()) {
            $partnerChat->addViewedByUser($partner->getUser()->getMyManager());
        }
        return $partnerChat;
    }


    /**
     * Высчитывает общий бонус партнера и возвращает его
     */
    public function partnerGetBonus(Partner $partner)
    {
        $bonusHistory = $partner->getBonusHistory();
        $bonus = 0;
        foreach ($bonusHistory as $key => $calcItem) {
            if (isset($calcItem['bonus']) && $calcItem['bonus']) {
                $bonus += intval($calcItem['bonus']);
                continue;
            }
            $bank = $this->bankMainRepository->find($calcItem['bank']);
            if (!$bank) return 0;
            $bonus += ($calcItem['summ'] * $bank->getBonusProcent()) / 100;
        }
        return intval(round($bonus, 0, PHP_ROUND_HALF_UP));
    }
}
