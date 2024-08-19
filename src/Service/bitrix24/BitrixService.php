<?php

namespace App\Service\bitrix24;

use App\Entity\BankMain;
use App\Entity\EmployeeRefLink;
use App\Entity\User;
use App\Entity\Buyer;
use App\Entity\Offer;
use App\Entity\Partner;
use App\Entity\Attachment;
use App\Entity\Calculated;
use App\Repository\BankMainRepository;
use App\Service\OfferService;
use App\Service\Wallet\WalletService;
use App\Service\Banks\BanksCalculator;
use App\Service\bitrix24\Crest as CRest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;


//define('C_REST_CLIENT_ID','local.5c8bb1b0891cf2.87252039');//Application ID
//define('C_REST_CLIENT_SECRET','SakeVG5mbRdcQet45UUrt6q72AMTo7fkwXSO7Y5LYFYNCRsA6f');//Application key
// or
// https://ssik.bitrix24.ru/rest/172/zf3seo7ohsoue4u0/
define('C_REST_WEB_HOOK_URL', 'https://ssik.bitrix24.ru/rest/6426/nupy6sgdooqw0jtk/'); //url on creat Webhook
//define('C_REST_CURRENT_ENCODING','windows-1251');
define('C_REST_IGNORE_SSL', true); //turn off validate ssl by curl
define('C_REST_LOG_TYPE_DUMP', true); //logs save var_export for viewing convenience
define('C_REST_BLOCK_LOG', true); //turn off default logs
//define('C_REST_LOGS_DIR', __DIR__ .'/logs/'); //directory path to save the log
define('BITRIX_DIRECTORT_ID', 118670);



//Фильтрует банки, выводит сообщения об ошибках
class BitrixService
{
    public $em;
    public $client;
    public $helper;
    public $banksCalculator;
    public $walletService;
    private $params;
    private $offerService;
    private $bankMainRep;



    public function __construct(
        EntityManagerInterface $em,
        HttpClientInterface $client,
        UploaderHelper $helper,
        BanksCalculator $banksCalculator,
        WalletService $walletService,
        ContainerBagInterface $params,
        OfferService $offerService,
        BankMainRepository $bankMainRepository
    ) {
        $this->walletService = $walletService;
        $this->em = $em;
        $this->client = $client;
        $this->helper = $helper;
        $this->banksCalculator = $banksCalculator;
        $this->params = $params;
        $this->offerService = $offerService;
        $this->bankMainRep = $bankMainRepository;
    }


    public function isActive(): bool
    {
        return boolval($this->params->get('isBitrixActive'));
    }



    public function contactGet(int $contactID)
    {
        $result = CRest::call(
            'crm.contact.get',
            [
                'id' => $contactID,
            ]
        );
        if (isset($result['error'])) {
            //dd($result);
            return false;
            // throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        return $result['result'];
    }


    public function contactTrueFind(string $phoneNumber)
    {
        $clearPhone = str_replace(["-", " ", "(", ")"], "", $phoneNumber);
        $result = CRest::call(
            'crm.duplicate.findbycomm',
            [
                'entity_type' => "CONTACT",
                'type' => "PHONE",
                'values' => [$clearPhone],
            ]
        );
        if (isset($result['error'])) {
            //dd($result);
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        if (!$result['result']) {
            return null;
        }

        if ($this->params->get('isBitrixMultiContacts')) {
            $dublicatesData = CRest::call(
                'crm.contact.list',
                [
                    'order' => ["DATE_CREATE" => "ASC"],
                    'filter' => ["ID" => $result['result']['CONTACT']],
                    'select' => ['ID', "NAME", "SECOND_NAME", "LAST_NAME"],
                ]
            );
            return $dublicatesData['result'];
        } else {
            return [$this->contactGet($result['result']['CONTACT'][0])];
        }
    }



    public function findContactByEmail(string $email)
    {
        $result = CRest::call(
            'crm.duplicate.findbycomm',
            [
                'entity_type' => "CONTACT",
                'type' => "EMAIL",
                'values' => [$email],
            ]
        );
        if (isset($result['error'])) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        if (!$result['result']) {
            return null;
        }
        return $result['result']['CONTACT'][array_key_first($result['result']['CONTACT'])];
    }



    public function cotactFindByPartner(Partner $partner)
    {
        if ($partner->getBitrixContactID()) {
            $exist = $this->contactGet($partner->getBitrixContactID());
            if ($exist) return $exist;
        }
        $result = CRest::call(
            'crm.contact.list',
            [
                'order' => ["DATE_CREATE" => "ASC"],
                'filter' => ["SOURCE_DESCRIPTION" => "ID пользователя сервиса:" . $partner->getUser()->getId()],
                'select' => ['ID', "NAME", "SECOND_NAME", "LAST_NAME"],
            ]
        );
        if (isset($result['error'])) {
            //dd($result);
            // throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
            return false;
        };
        if (!$result['result']) {
            return false;
        }
        return $this->contactGet(intval($result['result'][0]["ID"]));
    }



    public function contactAddPartner(Partner $partner, $purposeReg, ?EmployeeRefLink $employeeRefLink)
    {
        //Если это менеджер или админ или тестовый режим, где в поле не число а строка
        if ($partner->getBitrixContactID() && !intval($partner->getBitrixContactID())) {
            // dd('Пытаемся поменять менеджера');
            return 'admin';
        }

        //Если битрикс выключен
        if (!$this->isActive()) {
            return 'test';
        }
        //Обновляем контакт

        //Находим имеющиеся контакт по номеру телефона, массив дублей
        $existIDArray = $this->contactTrueFind($partner->getUser()->getPhone());

        $needUpdateID = false;
        if ($existIDArray) {
            if ($this->params->get('isBitrixMultiContacts')) {
                //На каждого партнера создаётся отдельный акк, только если не совпадают все данные
                $existContact = $this->compareFIOPartner($partner, $existIDArray);
                if ($existContact) {
                    //Нашёлся контакт который совпадает по ФИО
                    $needUpdateID = intval($existContact);
                }
            } else {
                //Если нет мультиконтакта, берём просто ID первого контакта
                $needUpdateID = intval($existIDArray[0]["ID"]);
            }
        }
        $contactData = $this->createPartnerContactData($partner, $purposeReg);

        $contactData['fields']["UF_CRM_1681128433910"] = "0";
        if (!is_null($employeeRefLink)) {
            $contactData['fields']["UF_CRM_669A33391B5D0"] = $employeeRefLink->getBitrixId();
            if ($employeeRefLink->getAgentId()) {
                $contactData['fields']['UF_CRM_66B9BA00636A6'] = $employeeRefLink->getAgentId();
            }
        }

        if ($needUpdateID) { //Контакт есть, создавать не надо
            $contactData['id'] = $needUpdateID;
            $isUpdated = CRest::call('crm.contact.update',  $contactData);
            if ($isUpdated) {
                $result = [
                    'result' => $needUpdateID,
                ];
            }
        } else { //Иначе создаём новый контакт
            $contactData['PHONE'] = [[
                "VALUE" => $partner->getUser()->getPhone(),
                "VALUE_TYPE" => "WORK",
            ]];
            $result = CRest::call('crm.contact.add',  $contactData);
        }

        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        $newID = intval($result['result']);
        $partner->setBitrixContactID($newID);
        $this->em->persist($partner);
        $this->em->flush();
        return $newID;
    }


    public function contactUpdatePartner(Partner $partner, $isDocs = null)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            return 'test';
        }
        //Если это менеджер или админ или тестовый режим, где в поле не число а строка
        if ($partner->getBitrixContactID() && !intval($partner->getBitrixContactID())) {
            // dd('Пытаемся поменять менеджера');
            return $partner->getBitrixContactID();
        }

        $existIDArray = $this->cotactFindByPartner($partner);
        if (!$existIDArray) return false;
        if (intval($existIDArray["ID"]) !== $partner->getBitrixContactID()) {
            $partner->setBitrixContactID(intval($existIDArray["ID"]));
            $this->em->persist($partner);
            $this->em->flush();
        }
        $contactData = $this->createPartnerContactData($partner);
        $contactData['id'] = $partner->getBitrixContactID();

        switch ($partner->getType()) {
            case 1:
                $contactData['fields']["UF_CRM_1631251901"] = "15686";
                break;
            case 2:
                $contactData['fields']["UF_CRM_1631251901"] = "15688";
                break;
            case 3:
                $contactData['fields']["UF_CRM_1631251901"] = "15690";
                break;
            case 4:
                $contactData['fields']["UF_CRM_1631251901"] = "15692";
                break;
        }

        if ($isDocs == "1") {
            $contactData['fields']["UF_CRM_1681128433910"] = "1";

            $timelineMessage = "Реквизиты изменены\n";
            $timelineMessage .= "Наименование банка получателя: " . $partner->getBankname()  . "\n";
            $timelineMessage .= "БИК: " . $partner->getBankbik()  . "\n";
            $timelineMessage .= "Расчетный счет: " . $partner->getBankaccount()  . "\n";

            $this->commentsAddContact($contactData['id'], $timelineMessage);
            $this->requisiteCreate($partner, $contactData['id']);
            $this->dealContactCreatePartnershipRequest($partner, $contactData['id']);
        }

        $isUpdated = CRest::call('crm.contact.update',  $contactData);
        return $isUpdated;
    }

    public function requisiteCreate(Partner $partner, $CONTACT_ID)
    {
        $requisiteData = [];

        $partnerType = $partner->getType();

        $requisiteList = CRest::call("crm.requisite.list", [
            'filter' => ["ENTITY_ID" => $CONTACT_ID]
        ])["result"];

        $parceName = explode(" ", $partner->getFullname());

        if ($partnerType == 1 || $partnerType == 2) {
            $requisiteData["PRESET_ID"] = "5";

            $requisiteData["RQ_LAST_NAME"] = $parceName[1] ?? "";
            $requisiteData["RQ_FIRST_NAME"] = $parceName[0] ?? "";
            $requisiteData["RQ_SECOND_NAME"] = $parceName[2] ?? "";
        } else if ($partnerType == 3) {
            $requisiteData["PRESET_ID"] = "3";

            if (count($parceName) == 4) {
                $requisiteData["RQ_LAST_NAME"] = $parceName[2] ?? "";
                $requisiteData["RQ_FIRST_NAME"] = $parceName[1] ?? "";
                $requisiteData["RQ_SECOND_NAME"] = $parceName[3] ?? "";
            } else {
                $requisiteData["RQ_LAST_NAME"] = $parceName[1] ?? "";
                $requisiteData["RQ_FIRST_NAME"] = $parceName[0] ?? "";
                $requisiteData["RQ_SECOND_NAME"] = $parceName[2] ?? "";
            }

            $requisiteData["RQ_INN"] = $partner->getInn();
        } else if ($partnerType == 4) {
            $requisiteData["PRESET_ID"] = "1";

            $requisiteData["RQ_INN"] = $partner->getInn();
            $requisiteData["RQ_COMPANY_FULL_NAME"] = $partner->getFullname();
            $requisiteData["RQ_ADDR"] = $partner->getLegaladress();
            $requisiteData["RQ_KPP"] = $partner->getOgrn();
        }

        $requisiteFileds = [
            "fields" => [
                "NAME" => $partner->getFullname(),
                'ENTITY_TYPE_ID' => 3,
                'ENTITY_ID' => $CONTACT_ID,
            ]
        ];

        foreach ($requisiteData as $key => $value) {
            $requisiteFileds["fields"][$key] = $value;
        }

        $requisiteId = 0;

        if (count($requisiteList) != 0) {
            if ($requisiteList[0]["PRESET_ID"] != $requisiteData["PRESET_ID"]) {
                CRest::call("crm.requisite.delete", ["ID" => $requisiteList[0]["ID"]])["result"];
                unset($requisiteList[0]);
            }
        }

        $requisiteId = CRest::call("crm.requisite.add", $requisiteFileds)["result"];

        if (count($requisiteList) == 0) {
            CRest::call("crm.requisite.add", $requisiteFileds)["result"];
            $requisiteId = CRest::call("crm.requisite.add", $requisiteFileds)["result"];
        } else {
            $requisiteId = $requisiteList[0]["ID"];
            $requisiteFileds["ID"] = $requisiteId;
            CRest::call("crm.requisite.update", $requisiteFileds)["result"];
        }

        $requisiteBankList = CRest::call("crm.requisite.bankdetail.list", [
            'filter' => ["ENTITY_ID" => $requisiteId]
        ])["result"];

        $bankFiels = [
            "fields" => [
                "NAME" => "Банковские реквизиты: " . " " . $partner->getFullname(),
                'ENTITY_TYPE_ID' => 8,
                'ENTITY_ID' => $requisiteId,
                "RQ_BANK_NAME" => $partner->getBankname(),
                "RQ_BIK" => $partner->getBankbik(),
                "RQ_ACC_NUM" => $partner->getBankaccount() ?? "",
            ]
        ];

        if (count($requisiteBankList) == 0) {
            CRest::call("crm.requisite.bankdetail.add", $bankFiels);
        } else {
            $bankFiels["id"] = $requisiteBankList[0]["ID"];
            CRest::call("crm.requisite.bankdetail.update", $bankFiels);
        }
    }

    public function dealContactCreatePartnershipRequest(Partner $partner, $CONTACT_ID)
    {
        CRest::call("crm.deal.add", [
            "fields" => [
                'TITLE' => $partner->getFullname() . "Запрос на партнерство",
                'STAGE_ID' => "C32:NEW",
                'CATEGORY_ID' => 32,
                "CONTACT_ID" => $CONTACT_ID,
                "UF_CRM_1633898407" => "15966",
            ]
        ]);
    }


    public function contactAddBuyer(Buyer $buyer)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            return 'test';
        }
        $existIDArray = $this->contactTrueFind($buyer->getPhone());
        $needUpdateID = false;
        if ($existIDArray) {
            if ($this->params->get('isBitrixMultiContacts')) {
                /**
                 * На каждого заемщика создаётся отдельный акк, только если не совпадают все данные
                 */
                $existContact = $this->compareFIOBuyer($buyer, $existIDArray);
                if ($existContact) { //Нашёлся контакт который совпадает по ФИО
                    $needUpdateID = intval($existContact);
                }
            } else { //Если нет мультиконтакта, берём просто ID первого контакта
                $needUpdateID = intval($existIDArray[0]["ID"]);
            }
        }
        // dd($needUpdateID);
        $contactData = [
            'fields' => [
                "NAME" => $buyer->getFirstname(),
                "SECOND_NAME" => $buyer->getMiddlename(),
                "LAST_NAME" => $buyer->getLastname(),
                "TYPE_ID" => "1",
                "SOURCE_DESCRIPTION" => "APP ID заёмщика:" . $buyer->getId(),
                "UF_CRM_1631249712697" => $buyer->getPasportSeries(),
                "UF_CRM_1631249750550" => $buyer->getPasportNum(),
                "UF_CRM_1631249794388" => $buyer->getPasportCode(),
                "UF_CRM_1631249838746" => $buyer->getPasportDescript(),
                "UF_CRM_1631249881114" => $buyer->getPasportDate(),
                "UF_CRM_1631249909263" => $buyer->getPassportAddress(),
                "UF_CRM_1631250022343" => $buyer->getAddress(),
                "BIRTHDATE" => $buyer->getBirthDate(),
            ],
            "params" => [
                "REGISTER_SONET_EVENT" => "Y"
            ]
        ];
        if ($needUpdateID) { //Контакт есть, создавать не надо
            $contactData['id'] = $needUpdateID;
            $isUpdated = CRest::call('crm.contact.update',  $contactData);
            if ($isUpdated) {
                $result = [
                    'result' => $needUpdateID,
                ];
            }
        } else { //Иначе создаём новый контакт
            $contactData['fields']['PHONE'] = [[
                "VALUE" => $buyer->getPhone(),
                "VALUE_TYPE" => "WORK",
            ]];
            $result = CRest::call('crm.contact.add',  $contactData);
        }
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        $newContactId = intval($result['result']);
        $buyer->setBitrixContactID($newContactId);
        $this->em->persist($buyer);
        $this->em->flush();
        return $newContactId;
    }


    public function folderGet(int $folderId)
    {
        $children = CRest::call(
            'disk.folder.getchildren',
            [
                'id' => $folderId,
            ]
        );
        return $children;
    }


    public function folderFindChildrens(int $folderId)
    {
        $childrens = CRest::call(
            'disk.folder.getchildren',
            [
                'id' => $folderId,
            ]
        );
        if (isset($childrens['error']) || !isset($childrens['result'])) {
            throw new \Exception(json_encode($childrens, JSON_UNESCAPED_UNICODE));
        };
        $result = [];
        foreach ($childrens['result'] as $child) {
            if ($child['NAME'] === "Документы Заемщик") {
                $result['person_docs'] = intval($child["ID"]);
            }
            if ($child['NAME'] === "Документы Объект") {
                $result['object_docs'] = intval($child["ID"]);
            }
        }
        return $result;
    }



    public function folderUploadFile(Attachment $attach, int $folderID)
    {
        $request = CRest::call(
            'disk.folder.uploadfile',
            [
                'id' => $folderID,
                "data" => [
                    "NAME" => $attach->getFileName(),
                ],
                "fileContent" => base64_encode(file_get_contents($attach->getFile()->getRealPath())),
            ]
        );

        if (!isset($request['result']['field'])) {
            throw new \Exception('Файл не удалось загрузить в Bitrix24');
        }

        return "ok";
    }



    public function dealGet(int $id)
    {
        $deal = CRest::call(
            'crm.deal.get',
            [
                'id' => $id,
            ]
        );
        if (isset($deal['error']) || !$deal['result']) {
            throw new \Exception(json_encode($deal, JSON_UNESCAPED_UNICODE));
        };
        return $deal['result'];
    }


    public function dealCreate(Calculated $calc, int $isPersonal)
    {
        $trustContact = intval($calc->getOffer()->getUser()->getPartner()->getBitrixContactID());
        if (!$trustContact || !$this->contactGet($trustContact)) {
            $trustContact = 53292;
        }
        $other = $calc->getOther();
        $buyer = $calc->getOffer()->getBuyer();
        $newDealFields = [
            'fields' => [
                'TITLE' => $buyer->getLastname() . " " . $buyer->getFirstname() . " " . $buyer->getPhone() . " - ipoteka.life",
                'STAGE_ID' => "NEW",
                "CURRENCY_ID" => "RUB",
                "OPPORTUNITY" => intval($calc->getFullsumm()),
                "CONTACT_ID" => $trustContact,
                "SOURCE_ID" => "79626142446",
                "SOURCE_DESCRIPTION" => "id заявки в сервисе:" . $calc->getId(),
                "UF_CRM_1635839486063" => "https://lk.ipoteka.life/suboffer/" . $calc->getId(),
                "UF_CRM_1624863778665" => $calc->getOffer()->getId() . "-" . $calc->getId(), //id заявки
                "UF_CRM_1723983762" => $calc->getOffer()->getTown()->getTitle(), //Область
                // "UF_CRM_1604996093" => $calc->getOffer()->getTown()->getTitle(), //Область
                "UF_CRM_ADDRESS_CITY" => $calc->getOffer()->getLocality(), //Город
                "UF_CRM_1623758496" => [ //id банка
                    0 => $calc->getBank()::BITRIX_ID[$calc->getBank()->getTitle()],
                ],
                "UF_CRM_1607226789746" => $this->convertCreditTarget($calc), //тип кредита
                "UF_CRM_1594453519428" => $this::BITRIX24_OBJECT_TYPE[$calc->getOffer()->getObjectType()], //тип недвижимости
                "UF_CRM_1607227334869" => $calc->getMonthcount(), //срок ипотеки
                "UF_CRM_1630502075323" => $calc->getOffer()->getIsMilitaryMortgage() ? 15668 : $calc->getOffer()->getStateSupport(),
                "UF_CRM_1607227512372" => intval($calc->getOffer()->getIsMotherCap()),
                "UF_CRM_1609173670" => intval($calc->getMotherCapSize()), //Размер остатка мат. капитала
                "UF_CRM_1594110064146" => $calc->getFirstpay(), //Первоначальный взнос
                "UF_CRM_1607226935127" => $this::BITRIX24_SALER_TYPE[$calc->getOffer()->getSalerType()], //тип продавца
                "UF_CRM_1607228319502" => $this::BITRIX24_HIRING_TYPE[$calc->getOffer()->getHiringType()], //работа
                "UF_CRM_1607227863363" => $this::BITRIX24_PROOF_MONEY_TYPE[$calc->getOffer()->getProofMoney()],
                "UF_CRM_1609173695" => $calc->getOffer()->getAge(),
                "UF_CRM_1623809701164" => $this::BITRIX24_NATIONALITY_TYPE[$calc->getOffer()->getNationality()], //гражданство
                "UF_CRM_1609173482" => \array_search($calc->getOffer()->getCalcPriceType(), $calc->getOffer()::CALC_PRICE_TYPE),
                "UF_CRM_1609173379" => $calc->getMounthpay(),
                "UF_CRM_1594109986495" => intval($calc->getFullsumm()),
                "UF_CRM_1599384261" => 118670, //Ссылка на папку ОБ
                "UF_CRM_1681126317477" => $isPersonal, // Если сделка личная,
                "UF_CRM_1721669202506" => $this::BITRIX24_TARGET_TYPE[(int)$calc->getOffer()->getIsTarget()], // Целевой\не целевой залог,
            ],
            "params" => ["REGISTER_SONET_EVENT" => "Y"],
        ];
        if (!is_null($calc->getOffer()->getIjs())) {
            $newDealFields['fields']["UF_CRM_1722501310772"] = $calc->getOffer()->getIjs();
        }
        /**
        При отправки заявки записывать в Б24
        - выбранный размер комиссии в поле (сделки ЗАЯВКА) Процент комиссии АРР ID
        поля UF_CRM_1689055541685
        - итоговую ставку в поле Динамическая ставка АРР (сделка ЗАЯВКА) ID поля
        UF_CRM_1689055687086 (ставка по программе + изменение
        ставки=Динамическая ставка, то что используется в дальнейшем в расчетах)
         * */
        if ($other['dinamic']){
            $newDealFields['fields']["UF_CRM_1689055687086"] = $other['dinamic']['rate'];
            $newDealFields['fields']["UF_CRM_1689055541685"] = $other['dinamic']['feePercent'];
        }

        /**
         * Передавать данные в Б24 из полей
        - Размер доп суммы (ID поля в Б24 UF_CRM_1691656452491 )
        - Количество консолидируемых кредитов (ID поля в Б24 UF_CRM_1691656498527)
         */
        if ($calc->getOffer()->getCreditTarget() === 'рефинансирование'){
            $bank = $this->em->getRepository(BankMain::class)->findOneBy(['id' => $other['bankId']]);
            if ($bank->isWithAddAmountEnabled()){
                if ($other['withAddAmount'] && $other['addAmount']){
                    $newDealFields['fields']["UF_CRM_1691656452491"] = $other['addAmount'];
                }
            }
            if ($bank->isWithConsolidationEnabled()){
                if ($other['withConsolidation'] && $other['creditsCount']){
                    $newDealFields['fields']["UF_CRM_1691656498527"] = $other['creditsCount'];
                }
            }
        }

        //TODO если новая версия банков, то подставляем в битрикс24 его ID
        if (isset($calc->getOther()['bankId']) && $calc->getOther()['bankId']) {
            $bankMain = $this->bankMainRep->find($calc->getOther()['bankId']);
            if ($bankMain && isset($bankMain->getOther()['bitrixId']) && $bankMain->getOther()['bitrixId']) {
                $newDealFields['fields']['UF_CRM_1623758496'] = $bankMain->getOther()['bitrixId'];
            }
        }

        //Если битрикс выключен
        if (!$this->isActive()) {
            return $calc;
        }
        $newDeal = CRest::call('crm.deal.add', $newDealFields);

        if (isset($newDeal['error']) || !$newDeal['result']) {
            //dd($newDeal);
            throw new \Exception(json_encode($newDeal, JSON_UNESCAPED_UNICODE));
        };


        CRest::call(
            'crm.deal.contact.add',
            [
                'id' => $newDeal['result'],
                'fields' => [
                    "CONTACT_ID" => $this->contactAddBuyer($calc->getOffer()->getBuyer()),
                ]
            ]
        );

        //добавляем созаемщиков
        foreach ($calc->getOffer()->getCobuyers() as $cobuyer) {
            CRest::call(
                'crm.deal.contact.add',
                [
                    'id' => $newDeal['result'],
                    'fields' => [
                        "CONTACT_ID" => $this->contactAddBuyer($cobuyer),
                    ]
                ]
            );
        }


        $calc->setBitrixID($newDeal['result']);

        //Если сделка по 2 документам, то нужно в комментарий добавить инфу о доходах
        $offer = $calc->getOffer();
        $comment = "[B]Данные по доходам:[/B]\n\n";
        if ($offer->getProofMoney() === 30) {

            $comment .= $this->is2docFormatWorkInfo($buyer);

            foreach ($offer->getCobuyers() as $cobuyer) {
                $comment .= $this->is2docFormatWorkInfo($cobuyer);
            }

            $this->commentsAdd($calc, $comment);

        }

        //Пробуем получить папки по сделке
        $newDeal = $this->dealGet($newDeal['result']);
        try {
            $folders = $this->folderFindChildrens($newDeal['UF_CRM_1599384236']);
            $calc->setBittrixObjectFolderId($folders['object_docs']);
            $calc->setBittrixPersonFolderId($folders['person_docs']);
        } catch (\Throwable $th) {
            $calc->setBittrixObjectFolderId(154260);
            $calc->setBittrixPersonFolderId(154260);
        }

        return $calc;
    }



    public function dealDownload(Calculated $calc)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            //dump('Обновление заявки. Тест');
            return true;
        }

        $oldCalcStatus = $calc->getStatus();
        $dealData = $this->dealGet($calc->getBitrixID());
        $calc->setStatus(Offer::BITRIX24_STATUS[$dealData['STAGE_ID']]);

        if ($calc->getStatus() >= 60) {
            $calc->setTruefullsumm(intval($dealData['UF_CRM_1594453674628']));
            $calc->setTrueprocent(floatval($dealData['UF_CRM_1594453768376']));
            $calc->setTruemounthpay(intval($dealData['UF_CRM_1630401298431']));
            $calc->setTruefirstpay(floatval($dealData['UF_CRM_1594453735873']));
            $calc->setTruemonthcount($calc->getMonthcount());
        }
        if ($calc->getStatus() == 130) {
            //Если кредит выдали, то считаем по выданной сумме
            $calc->setTruefullsumm(intval($dealData['UF_CRM_1594109873907']));
        }
        if ($calc->getStatus() >= 115) {
            $calc->setSignData($dealData['UF_CRM_1594080576867']);
            $other = $calc->getOther();

            $simpifyAdress = $dealData['UF_CRM_1625732355470'];
            if (strpos($simpifyAdress, "|")) {
                $simpifyAdress = explode("|", $simpifyAdress)[0];
            }

            //Обрабатываем данные специалиста
            $creditManager = $this->contactGet($dealData['UF_CRM_1625732726']);
            if (isset($creditManager['LAST_NAME']) && isset($creditManager['NAME'])) {
                $creditManager = $creditManager['LAST_NAME'] . " " . $creditManager['NAME'] . " " . $creditManager['SECOND_NAME'];
            } else {
                $creditManager = "Нет данных. Обратитесь в техподдержку";
            }

            $other['trueSingDate'] = $dealData['UF_CRM_1615353080615']; //Дата и время сделки
            $other['address'] = $simpifyAdress; //Адрес ИЦ
            $other['manager'] = $creditManager; //Специалист
            $other['nalog'] = $dealData['UF_CRM_1625733018508']; //Сумма расходов по сделке
            $other['information'] = $dealData['UF_CRM_1625828151307']; //Доп. информация по сделке
            $calc->setOther($other);
        }

        //Обрабатываем комментарии об ошибках
        $other = $calc->getOther();
        $other['offerErrorComment'] = $dealData["UF_CRM_1623940970509"];
        $other['objectErrorComment'] = $dealData["UF_CRM_1623941011742"];
        $calc->setOther($other);
        // dd($calc);
        $this->em->persist($calc);
        $this->em->flush();

        return $dealData;
    }




    public function dealUploadPersonDocs(Offer $offer)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            return "Загрузка документов по пользователю. Тест.";
        }
        $offerDocs = $offer->getDocuments();
        $calcs = $offer->getCalculateds();
        if (!$calcs[0]->getBittrixPersonFolderId()) {
            throw new \Exception("Отсутствует идентификатор папки документов Bitrix24 заёмщика по сделке: " . $offer->getId());
        }
        $result = [];
        foreach ($offerDocs as $attach) {
            foreach ($calcs as $calc) {
                try {
                    $result[] = $this->folderUploadFile($attach, $calc->getBittrixPersonFolderId());
                } catch (\Throwable $th) {
                    $result[] = $th->getMessage() . " | " . "Ошибка загрузки документа. Возможно файл уже есть";
                }
            }
        }
        return $result;
    }




    public function dealUploadObjectDocs(Calculated $calculated)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            return "Загрузка документов по пользователю. Тест.";
        }
        $objectDocs = $calculated->getObjectDocs();
        if (!$calculated->getBittrixObjectFolderId()) {
            throw new \Exception("Отсутствует идентификатор папки документов Bitrix24 объекта по сделке: " . $calculated->getId());
        }
        $result = [];
        foreach ($objectDocs as $attach) {
            try {
                $result[] = $this->folderUploadFile($attach, $calculated->getBittrixObjectFolderId());
            } catch (\Throwable $th) {
                $result[] = "Ошибка загрузки документа. Возможно файл уже есть";
            }
        }
        return $result;
    }




    public function сommentsGet(Calculated $calc)
    {
        $comments = CRest::call(
            'crm.timeline.comment.list',
            [
                "filter" => [
                    "ENTITY_ID" => $calc->getBitrixID(),
                    "ENTITY_TYPE" => 'deal',
                ],
                'order' => [
                    'CREATED' => 'ASC',
                ],
            ]
        );
        if (isset($comments['error']) || !isset($comments['result'])) {
            throw new \Exception(json_encode($comments, JSON_UNESCAPED_UNICODE));
        };

        $result = array_map(function ($comment) {
            $comment['isClient'] = false;
            if (mb_strstr($comment['COMMENT'], "[b]КЛИЕНТ:[/b] ")) {
                $comment['COMMENT'] = str_replace("[b]КЛИЕНТ:[/b] ", "", $comment['COMMENT']);
                $comment['isClient'] = true;
            }
            $comment['COMMENT'] = str_replace("\n", "<br>", $comment['COMMENT']);
            return $comment;
        }, $comments["result"]);
        return $result;
    }

    public function commentsAddContact($id, string $message)
    {
        $newComment = CRest::call('crm.timeline.comment.add', [
            "fields" =>
            [
                "ENTITY_ID" => $id,
                "ENTITY_TYPE" => "contact",
                "COMMENT" => $message
            ]
        ]);

        if (isset($newComment['error']) || !isset($newComment['result'])) {
            throw new \Exception(json_encode($newComment, JSON_UNESCAPED_UNICODE));
        };

        return $newComment["result"];
    }

    public function commentsAddDeal($id, string $message)
    {
        $newComment = CRest::call('crm.timeline.comment.add', [
            "fields" =>
            [
                "ENTITY_ID" => $id,
                "ENTITY_TYPE" => "deal",
                "COMMENT" => $message
            ]
        ]);

        if (isset($newComment['error']) || !isset($newComment['result'])) {
            throw new \Exception(json_encode($newComment, JSON_UNESCAPED_UNICODE));
        };

        return $newComment["result"];
    }

    public function commentsAdd(Calculated $calc, string $message)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            return "Добавление комментария - $message. Тест.";
        }

        //TODO решить что делать с добавлением комментария
        // вызов этого метода вызывает ошибку Битрикс "Livefeed is no longer supported"

        $newComment = CRest::call(
            'crm.timeline.comment.add',
            [
                "fields" => [
                    "ENTITY_ID" => $calc->getBitrixID(),
                    "ENTITY_TYPE" => 'deal',
                    "COMMENT" => $message,
                ],
            ]
        );
        if (isset($newComment['error']) || !isset($newComment['result'])) {
            throw new \Exception(json_encode($newComment, JSON_UNESCAPED_UNICODE));
        };
        return $newComment["result"];

        //return "Добавление комментария - $message. Тест.";

    }


    public function setBitrixStatus(Calculated $calculated, string $status)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            //dump($status);
            return 'test';
        }
        $dealFields = [
            'id' => $calculated->getBitrixID(),
            'fields' => [
                'STAGE_ID' => $status,
            ],
            "params" => ["REGISTER_SONET_EVENT" => "N"],
        ];
        $deal = CRest::call('crm.deal.update', $dealFields);
        if (isset($deal['error']) || !$deal['result']) {
            //dd($deal);
            throw new \Exception(json_encode($deal, JSON_UNESCAPED_UNICODE));
        };
    }



    public function setSignDate(Calculated $calculated, string $signDate)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            //dump($signDate);
            return 'test';
        }

        $dealFields = [
            'id' => $calculated->getBitrixID(),
            'fields' => [
                'UF_CRM_1615353080615' => $signDate, //желаемая дата сделки
            ],
            "params" => ["REGISTER_SONET_EVENT" => "N"],
        ];
        $deal = CRest::call('crm.deal.update', $dealFields);
        if (isset($deal['error']) || !$deal['result']) {
            //dd($deal);
            throw new \Exception(json_encode($deal, JSON_UNESCAPED_UNICODE));
        };
    }


    public function getAssignedAdmin(User $user)
    {
        //Если битрикс выключен
        if (!$this->isActive()) {
            //dump($user);
            return 'test';
        }

        $userContact = $user->getPartner()->getBitrixContactID();
        $assignedUserID = intval($this->contactGet($userContact)['ASSIGNED_BY_ID']);
        $result = CRest::call('user.get', ['ID' => $assignedUserID]);
        if (isset($result['error']) || !$result['result']) {
            throw new \Exception(json_encode($result, JSON_UNESCAPED_UNICODE));
        };
        return $result['result'][0];
    }


    /**
     * Сравнивает ФИО заёмщика с переданным массиво данных контакта Bitrix24
     */
    public function compareFIOBuyer(Buyer $buyer, array $bitrixContactList)
    {
        /*
            Вид $bitrixContactList = [
                [
                    "ID" => "53292"
                    "NAME" => "Имя"
                    "SECOND_NAME" => "Отчетство"
                    "LAST_NAME" => "Фамилия"
                ], [...],
            ];
        */
        foreach ($bitrixContactList as $existContactData) {
            $firstName = mb_strtolower($existContactData['NAME']);
            $lastName = mb_strtolower($existContactData['LAST_NAME']);
            $middleName = mb_strtolower($existContactData['SECOND_NAME']);
            if (
                mb_strtolower($buyer->getFirstname()) == $firstName &&
                mb_strtolower($buyer->getLastname()) == $lastName &&
                mb_strtolower($buyer->getMiddlename()) == $middleName
            ) {
                //Если нашлось полное совпадение
                return $existContactData["ID"];
            }
        }
        return false;
    }


    public function compareFIOPartner(Partner $partner, array $bitrixContactList)
    {
        /*
            Вид $bitrixContactList = [
                [
                    "ID" => "53292"
                    "NAME" => "Имя"
                    "SECOND_NAME" => "Отчетство"
                    "LAST_NAME" => "Фамилия"
                ], [...],
            ];
        */
        foreach ($bitrixContactList as $existContactData) {
            $firstName = mb_strtolower($existContactData['NAME']);
            $lastName = mb_strtolower($existContactData['LAST_NAME']);
            $middleName = mb_strtolower($existContactData['SECOND_NAME']);
            if (
                mb_strtolower($partner->getUser()->getFirstname()) == $firstName &&
                mb_strtolower($partner->getUser()->getLastname()) == $lastName &&
                mb_strtolower($partner->getUser()->getMiddlename()) == $middleName
            ) {
                //Если нашлось полное совпадение
                return $existContactData["ID"];
            }
        }
        return false;
    }


    public function createPartnerContactData(Partner $partner, $purposeReg = "15982")
    {
        $contactData = [
            // 'id' => $existID,
            'fields' => [
                "NAME" => $partner->getUser()->getFirstname(),
                "SECOND_NAME" => $partner->getUser()->getMiddlename(),
                "LAST_NAME" => $partner->getUser()->getLastname(),
                "TYPE_ID" => "UC_O2ONHV",
                "SOURCE_ID" => "79626142446",
                "SOURCE_DESCRIPTION" => "ID пользователя сервиса:" . $partner->getUser()->getId(),
                "PHONE" => [[
                    "VALUE" =>  $partner->getUser()->getPhone(),
                    "VALUE_TYPE" => "WORK",
                ]],
                "EMAIL" => [[
                    "VALUE" => $partner->getUser()->getEmail(),
                    "VALUE_TYPE" => "WORK",
                ]],
                "ADDRESS_REGION" => $partner->getUser()->getTown()->getTitle(),
                "UF_CRM_1616431443" => $partner->getInn(),
                "UF_CRM_1631250195311" => $partner->getBankname(),
                "UF_CRM_1631250209721" => $partner->getBankbik(),
                "UF_CRM_1631250249497" => $partner->getBankaccount(),
                "UF_CRM_1683396341269" => $purposeReg,
                "UF_CRM_1681128433910" => "0",
                "COMMENTS" => $partner::PARTNER_TYPE[$partner->getType()],
            ],
            "params" => [
                "REGISTER_SONET_EVENT" => "Y"
            ]
        ];

        $contactData['fields']['UF_CRM_1631252200'] = "";
        $contactData['fields']['UF_CRM_1631252515655'] = $partner->getFullname();
        $contactData['fields']['UF_CRM_1631252641122'] = "";
        $contactData['fields']['UF_CRM_1631252664884'] = "";
        $contactData['fields']['UF_CRM_1631252707730'] = "";

        if ($partner->getType() === 3) {
            //ИП
            $contactData['fields']['UF_CRM_1631252515655'] = $partner->getFullname();
            switch ($partner->getNalogtype()) {
                case 'osno':
                    $contactData['fields']['UF_CRM_1631252200'] = 15696;
                    break;
                case 'usn':
                    $contactData['fields']['UF_CRM_1631252200'] = 15694;
                    break;
            }
        }
        if ($partner->getType() === 4) {
            //Юр. лицо
            $contactData['fields']['UF_CRM_1631252515655'] = $partner->getFullname();
            $contactData['fields']['UF_CRM_1631252641122'] = $partner->getLegaladress();
            $contactData['fields']['UF_CRM_1631252664884'] = $partner->getOgrn();
            $contactData['fields']['UF_CRM_1631252707730'] = $partner->getContactface();
            switch ($partner->getNalogtype()) {
                case 'osno':
                    $contactData['fields']['UF_CRM_1631252200'] = 15696;
                    break;
                case 'usn':
                    $contactData['fields']['UF_CRM_1631252200'] = 15694;
                    break;
            }
        }
        return $contactData;
    }



    private function convertCreditTarget(Calculated $calc)
    {
        $result = $this::BITRIX24_CREDIT_TYPE[$calc->getOffer()->getCreditTarget()];
        if ($result == 14160 && $calc->getOffer()->getProofMoney() == 30) {
            $result = 14338;
        }
        return $result;
    }



    public function sendNotyfToBitrix24(string $message, int $managerId)
    {
        $messageParams = [
            'USER_ID' => $managerId,
            'MESSAGE' => $message,
            'MESSAGE_OUT' => $message,
            'TAG' => 'TEST_LK',
        ];
        $response = CRest::call('im.notify.personal.add', $messageParams);
        return $response;
    }

    public function sendChatMessageToBitrix24(string $message)
    {
        $messageChatParams = [
            "DIALOG_ID" => "chat7422",
            "MESSAGE" => $message,
            "SYSTEM" => "N",
        ];
        $response = CRest::call('im.message.add', $messageChatParams);
        return $response;
    }

    public function is2docFormatWorkInfo(Buyer $buyer)
    {
        $otherDefault = [
            'isNotMoney' => false,
            'inn' => '',
            'work_phone' => '',
            'work_address' => '',
            'proff' => '',
            'work_money' => '',
            'work_year' => '',
            'work_month' => '',
        ];
        $other = array_merge($otherDefault, $buyer->getOther());
        $out = [];
        $out[] = "ФИО: " . $buyer->getFio();
        $out[] = "Есть доход?: " . ($other['isNotMoney'] ? 'нет' : 'есть');
        if (!$other['isNotMoney']) {
            $out[] = "ИНН раб.: " . $other['inn'];
            $out[] = "Телефон раб: " . $other['work_phone'];
            $out[] = "Факт. адрес раб.: " . $other['work_address'];
            $out[] = "Должность: " . $other['proff'];
            $out[] = "Ежемесячный доход: " . $other['work_money'];
            $out[] = "Стаж: " . $other['work_year'] . 'лет, ' . $other['work_month'] . 'мес.';
        }
        return implode("\n", $out) . "\n\n";
    }

    public function createReferalDeal(Partner $partner, Partner $referal, $referalBonus = 10000)
    {
        $resultId = CRest::call("crm.deal.add", [
            "fields" => [
                "TITLE" => trim($referal->getUser()->getFio()) . " Реферал. I.LIFE",
                "CATEGORY_ID" => 2,
                "STAGE_ID" => "C2:NEW",
                "CONTACT_ID" => $referal->getBitrixContactID(),
                "ASSIGNED_BY_ID" => "20",
                "UF_CRM_1681127333214" => $referalBonus,
            ]
        ])["result"];

        $referal->setBitrixReferalDealID($resultId);
        $referal->setReferalStatus(1);

        $timelineMessage = "";

        $timelineMessage .= "Пригласивший: " . trim($partner->getUser()->getFio()) . ", Тип: " . $partner::PARTNER_TYPE[$partner->getType()] . "\n";
        $timelineMessage .= "Приглашенный: " . trim($referal->getUser()->getFio()) . ", Тип: " . $referal::PARTNER_TYPE[$referal->getType()] . "\n";
        $timelineMessage .= "Бонус, (" . $referalBonus . "руб)";


        $this->commentsAddDeal($resultId, $timelineMessage);

        $this->em->persist($referal);
        $this->em->flush();
    }

    public function updateReferalDealBonus(Partner $partner, $bonus)
    {
        $dealId = $partner->getBitrixReferalDealID();

        if ($dealId) {
            CRest::call("crm.deal.update", [
                "id" => $dealId,
                "fields" => [
                    "UF_CRM_1681127333214" => $bonus,
                ]
            ]);
        }
    }

    public function updateReferalDealStage(Partner $partner, $fromStatus, $status)
    {
        $currStatus = $partner->getReferalStatus();

        // Если currStatus и fromStatus не совпадают, то не изменяем сделку
        if ($currStatus != $fromStatus) {
            return;
        }

        $dealId = $partner->getBitrixReferalDealID();

        if ($dealId) {
            CRest::call("crm.deal.update", [
                "id" => $dealId,
                "fields" => [
                    "STAGE_ID" => $this::BITRIX24_REFERAL_STATUS[$status],
                ]
            ]);

            $partner->setReferalStatus($status);

            $this->em->persist($partner);
            $this->em->flush();
        }
    }

    public function getReferalCurrStage(Partner $partner)
    {
        $dealId = $partner->getBitrixReferalDealID();

        if ($dealId) {
            $result = CRest::call("crm.deal.get", [
                "id" => $dealId,
            ]);

            return array_flip($this::BITRIX24_REFERAL_STATUS)[$result["result"]["STAGE_ID"]];
        }

        return 0;
    }

    public function getReferalbalanceDeal(Partner $partner)
    {
        $dealId = $partner->getBitrixReferalDealID();

        if ($dealId) {
            $result = CRest::call("crm.deal.get", [
                "id" => $dealId,
            ]);

            return (int) $result["result"]["UF_CRM_1681127333214"];
        }

        return 0;
    }

    public function getStatusPersonalDeal($dealId)
    {
        if ($dealId) {
            $result = CRest::call("crm.deal.get", [
                "id" => $dealId,
            ]);

            return (int) $result["result"]["UF_CRM_1681126317477"] == "1";
        }

        return false;
    }

    const BITRIX24_CREDIT_TYPE = [
        "ипотека" => 14160,
        "рефинансирование" => 14342,
        "материнский" => 14346,
        "залог" => 14344,
    ];

    const BITRIX24_OBJECT_TYPE = [
        "квартира" => 1984,
        "комната" => 1988,
        "дом" => 1986,
        "таунхаус" => 15958,
        "апартаменты" => 1984,
        "кн" => 15968,
        "ижс" => 15970,
        "отд_доля" => 16070,
        "участок" => 16072,
    ];

    const BITRIX24_SALER_TYPE = [
        "физ_по_дкп" => 14168,
        "застр_по_дду" => 14170,
        "ижс" => 16082,
        "юр_по_дкп" => 14172,
        "физ_по_дупт" => 15976,
        "юр_по_дупт" => 16060,
    ];

    const BITRIX24_HIRING_TYPE = [
        10 => 14180,
        20 => 14182,
        30 => 14184,
        40 => 15972,
    ];

    const BITRIX24_PROOF_MONEY_TYPE = [
        10 => 14174,
        20 => 14176,
        30 => 14178,
        40 => 15974,
        50 => 16062,
        60 => 16064,
        70 => 16066,
        80 => 16068,
    ];

    const BITRIX24_NATIONALITY_TYPE = [
        10 => 14472,
        20 => 14474,
        30 => 14476,
    ];

    const BITRIX24_REFERAL_STATUS = [
        0 => "C2:NEW",
        1 => "C2:NEW",
        2 => "C2:18",
        3 => "C2:19",
        4 => "C2:20",
        5 => "C2:21",
        6 => "C2:WON",
    ];

    const BITRIX24_TARGET_TYPE = [
        0 => 16032,
        1 => 16030
    ];
}
