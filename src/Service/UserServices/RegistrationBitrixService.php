<?php

namespace App\Service\UserServices;

use App\Entity\EmployeeRefLink;
use App\Entity\User;
use App\Entity\PreUser;
use App\Service\bitrix24\BitrixService;
use App\Service\bitrix24\Crest as CRest;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class RegistrationBitrixService
{
    public function __construct(
        private Security $security,
        private BitrixService $bitrixService,
        private ContainerBagInterface $params
    ) {
    }

    public function isActive(): bool
    {
        return boolval($this->params->get('isBitrixActive'));
    }

    public function clearPhone(string $phoneString): string
    {
        return str_replace(["-", " ", "(", ")"], "", $phoneString);
    }

    public function makeRegistrationLead(PreUser $preUser): int
    {

        if (!$this->isActive()) return 12345;
        if (!$preUser->getPhone()) throw new \Error('Номер телефона отсутствует в сущности PreUser');
        if ($preUser->getBitrixLeadId()) throw new \Error('Лид уже создан');
        $params = [
            'NAME' => 'Регистрация с lk.ipoteka.life',
            'STATUS_ID' => 'NEW',
            'ASSIGNED_BY_ID' => 236,
            'SOURCE_ID' => 79626142446,
            'OPENED' => 'Y',
            'IS_RETURN_CUSTOMER' => 'N',
            'UF_CRM_1555253746127' => 104, //Направление работы по клиенту
            'COMMENTS' => "Регистрация с сервиса lk.ipoteka.life. Попытка регистрации №{$preUser->getTryCount()}",
            'PHONE' => [[
                "VALUE" => $this->clearPhone($preUser->getPhone()),
                "VALUE_TYPE" => "WORK",
            ]],
            'EMAIL' => [[
                "VALUE" => $preUser->getEmail(),
                "VALUE_TYPE" => "WORK",
            ]],
        ];
        if (!is_null($preUser->getEmployeeRefLink())) {
            $params['UF_CRM_1721222427'] = $preUser->getEmployeeRefLink()->getBitrixId();
            if ($preUser->getEmployeeRefLink()->getAgentId()) {
                $params['UF_CRM_1723447779384'] = $preUser->getEmployeeRefLink()->getAgentId();
            }
        }
        $result = CRest::call('crm.lead.add',  [
            'fields' => $params,
            'params' => ['REGISTER_SONET_EVENT' => 'Y'],
        ]);
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        $newID = intval($result['result']);
        return $newID;
    }

    public function updateLeadComment(PreUser $preUser, string $email)
    {
        if (!$this->isActive()) return true;
        if (!$preUser->getBitrixLeadId()) throw new \Error('Ошибка. Лид не установлен!');
        $params = [
            'COMMENTS' => "Регистрация с сервиса lk.ipoteka.life. Попытка регистрации №{$preUser->getTryCount()}",
        ];
        if($preUser->getEmail() !== $email) {
            $params['EMAIL'] = [[
                "VALUE" => $email,
                "VALUE_TYPE" => "WORK",
            ]];
        }

        $result = CRest::call('crm.lead.update', [
            'id' => $preUser->getBitrixLeadId(),
            'fields' => $params,
        ]);
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        return $result['result']; //должно вернуть просто true
    }

    public function chanheLeadStatus(int $leadId, string $statusId)
    {
        if (!$this->isActive()) return true;
        $result = CRest::call('crm.lead.update', [
            'id' => $leadId,
            'fields' => [
                'STATUS_ID' => $statusId,
            ],
        ]);
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        return $result['result']; //должно вернуть просто true
    }

    public function makeRegDeal(User $user, ?EmployeeRefLink $employeeRefLink)
    {
        if (!$this->isActive()) return 12345;
        $params = [
            'fields' => [
                'TITLE' => $user->getFio() . "Регистрация - lk.ipoteka.life",
                'STAGE_ID' => "C30:NEW",
                'CATEGORY_ID' => 30,
                "CONTACT_ID" => $user->getPartner()->getBitrixContactID(),
                "COMMENTS" => "Регистрация в lk.ipoteka.life. Email подтвержден",
            ],
        ];
        if (!is_null($employeeRefLink)) {
            $params['fields']['UF_CRM_669A333B907B4'] = $employeeRefLink->getBitrixId();
            if ($employeeRefLink->getAgentId()) {
                $params['fields']['UF_CRM_66B9BA02571DE'] = $employeeRefLink->getAgentId();
            }
        }
        $result = CRest::call('crm.deal.add', $params);
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        $newID = intval($result['result']);
        return $newID;
    }
}
