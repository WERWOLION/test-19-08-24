<?php

namespace App\Service\Calculator;

use App\Entity\Offer;
use App\Entity\BankNum;
use App\Entity\BankMain;
use App\Entity\BankCheck;
use App\Entity\BankOption;
use App\Service\Calculator\BankSubEntites;

class BankTester
{
    public function __construct(
        private MathUtils $mathUtils,
        private PercentCalculator $percentCalculator,
    ) {
    }


    public function testAcceptBank(BankMain $bank, Offer $offer): BankSubEntites
    {
        //Фильтр по областям
        if (!in_array($offer->getTown()->getId(), $bank->getTowns())) {
            throw new \Exception('Банк не работает в регионе: ' . $offer->getTown()->getTitle());
        }

        /**
         * Фильтр по цели кредита
         * Проверяет поля hasIpoteka, hasRefinance, hasPledge
         */
        if (!$bank->getOther()[$this::creditTargets[$offer->getCreditTarget()]]) {
            throw new \Exception('Банк не предоставляет выбранную цель кредита');
        }

        //Фильтр по гражданству
        if ($offer->getNationality() !== 10 && !$bank->getOther()['isForMigrant']) throw new \Exception('Банк не работает с нерезидентами РФ');

        //Фильтр по мат капиталу
        if ($offer->getIsMotherCap() && !$bank->getOther()['hasMother']) throw new \Exception('Банк не предоставляет кредит, при наличии мат. капитала');

        /**
         * Проверяем и находим связанные с банком сущности по цели кредита - сущности BankOption.
         * Их может не быть в каком-то граничном случае
         */
        try {
            $bankOption = $bank->{$this::bankOptionsEntity[$offer->getCreditTarget()]}();
        } catch (\Throwable $th) {
            throw new \Exception('Банк не предоставляет выбранную цель кредита');
        }

        /**
         * Находим связанные сущности () по типу недвижимости из полученной в предыдущей проверке $bankOption,
         * getCheckFlat, getCheckRoom, getCheckHome, getCheckApartments.
         * Их может не быть в каком-то граничном случае
         */
        try {
            $bankCheck = $bankOption->{$this::bankCheckEntity[$offer->getObjectType()]}();
        } catch (\Throwable $th) {
            throw new \Exception('Ошибка определения типа недвижимости, либо банк не работает с этим типом недв.');
        }

        /**
         * Проверяем включены ли опции консолидации и доп. суммы
         */
        if ($offer->getCreditTarget() === 'рефинансирование'){
            if (!$bank->isWithAddAmountEnabled() && $offer->withAddAmount && $offer->addAmount){
                throw new \Exception('Банк не поддерживает рефинансирование с доп. суммой');
            }
            if (!$bank->isWithConsolidationEnabled() && $offer->withConsolidation && $offer->creditsCount){
                throw new \Exception('Банк не поддерживает рефинансирование с консолидацией');
            }
        }

        $this->testBankCheck($bank, $bankCheck, $bankOption, $offer);

        $result = new BankSubEntites();
        $result->bank = $bank;
        $result->bankCheck = $bankCheck;
        $result->bankOptions = $bankOption;

        $this->testStateSuppot($result, $offer);
        return $result;
    }


    public function testBankCheck(BankMain $bank, BankCheck $bankCheck, BankOption $bankOption, Offer $offer): void
    {

        if (!$bankCheck->getIsOn()) throw new \Exception('Банк не кредитует выбранный тип недвижимости');

        if ($offer->getProofMoney() === 10) {
            //У нас по 2НДФЛ общая галочка на все типы недвижимости. Поэтому берём из квартиры.
            if (!$bankOption->getCheckFlat()->getIsOnNDFL())  throw new \Exception('Банк не кредитует выбранный тип недвижимости, с подтверждением по 2НДФЛ');
        }

        if ($offer->getProofMoney() === 20) {
            //У нас по форме банка общая галочка на все типы недвижимости. Поэтому берём из квартиры.
            if (!$bankOption->getCheckFlat()->getIsOnBankForm()) throw new \Exception('Банк не кредитует выбранный тип недвижимости, с подтверждением по форме банка');
        }

        if ($offer->getProofMoney() === 30) {
            if (!$bankCheck->getIsOn2doc())  throw new \Exception('Банк не кредитует выбранный тип недвижимости по 2 документам');
            if ($offer->getNationality() !== 10 && !$bankCheck->getIsOn2docMigrant()) {
                throw new \Exception('Банк не кредитует выбранный тип недвижимости по 2 документам, если гражданство не РФ');
            }
            if ($offer->getIsMotherCap() && !$bank->getOther()['hasOn2docMother']) {
                throw new \Exception('Банк не кредитует выбранный тип недвижимости по 2 документам, если есть мат. капитал');
            }
            /**
             * если тип работы не найм (ИП или Бизнес) и подтверждение по 2 документам, то смотрим настройки банка
             * поддерживает ли он такое кредитование
             */
            if ($offer->getHiringType() !== 10 && $offer->getHiringType() !== 40 && !$bank->isProofMoney2docEnabled()){
                throw new \Exception('Банк не кредитует по 2 документам ИП или Бизнес');
            }
            /**
             * если тип работы Самозанятый и подтверждение по 2 документам, то смотрим настройки банка
             * поддерживает ли он такое кредитование
             */
            if ($offer->getHiringType() == 40 && !$bank->isProofMoney2docSelfEmployedEnabled()){
                throw new \Exception('Банк не кредитует по 2 документам Самозанятых');
            }
        }
        /**
         * Если бизнес и СФР как способ подтверждения дохода смотрим в настройках банка
         * */
        if ($offer->getProofMoney() === 70 && in_array($offer->getHiringType(), [20,30,40]) && !$bank->isproofMoneySfrBusinessEnabled()) {
            throw new \Exception('Банк не рассматривает данный вид подтверждения дохода');
        }
        /**
         * Если бизнес и "2НДФЛ как наемный сотрудник" как способ подтверждения дохода смотрим в настройках банка
         * */
        if ($offer->getProofMoney() === 60 && in_array($offer->getHiringType(), [20,30,40]) && !$bank->isProofMoney2ndflBusinessEnabled()) {
            throw new \Exception('Банк не рассматривает данный вид подтверждения дохода');
        }

        if ($offer->getObjectType() == "кн" && $offer->getIsMotherCap()) {
            throw new \Exception("При приобретении данного типа недвижимости нельзя использовать средства Материнского (семейного) капитала.");
        }
    }


    public function testStateSuppot(BankSubEntites $bankSubEntites, Offer $offer)
    {
        if ($offer->getStateSupport() === 15956) { //IT-ипотека
            if (!$bankSubEntites->bank->isIsIT()) throw new \Exception('В банке отсутствует данная программа кредитования. (IT-ипотека)');
            if ($offer->getCreditTarget() !== 'ипотека') throw new \Exception('Программа подходит только для приобретения недвижимости');
            if ($offer->getProofMoney() === 30) throw new \Exception('Программа IT-ипотека не предоставляется без подтверждения (по 2 документам)');
            if ($offer->getSalerType() === 'физлицо') throw new \Exception('Программа не подходит для кредитования недвижимости на вторичном рынке');
            if ($offer->getNationality() !== 10) throw new \Exception('Данная программа подходит только для граждан РФ');
            // if ($offer->getAge() > 55 || $offer->getAge() < 18) throw new \Exception('Возраст заемщика должен быть в пределах: 18-55 лет');
            if ($offer->getHiringType() !== 10) throw new \Exception('Программа подходит только для наемных сотрудников');
            if (!in_array($offer->getObjectType(), ['квартира', 'дом', 'таунхаус', 'ижс'])) throw new \Exception('Для IT-ипотеки не подходит выбранный тип недвижимости');
        }
        if ($offer->getStateSupport() === 15664) { //Льготная ипотека
            if ($offer->getCreditTarget() === 'рефинансирование') throw new \Exception('Льготная ипотека подходит только для приобретения недвижимости');
            if (!$bankSubEntites->bank->getIsSocial()) throw new \Exception('В банке отсутствует данная программа кредитования. (Льготная ипотека)');
            if ($offer->getSalerType() === 'физлицо') throw new \Exception('Программа не подходит для кредитования недвижимости на вторичном рынке');
        }
        if ($offer->getStateSupport() === 15666) { //Семейная ипотека
            if (!$bankSubEntites->bank->getIsFamily()) throw new \Exception('В банке отсутствует данная программа кредитования. (Семейная ипотека)');
            if ($offer->getSalerType() === 'физлицо') throw new \Exception('Программа не подходит для кредитования недвижимости на вторичном рынке');
        }
        if ($offer->getIsMilitaryMortgage()) { //Военная ипотека
            if (!$bankSubEntites->bank->getIsWarCap()) throw new \Exception('В банке отсутствует данная программа кредитования. (Военная ипотека)');
        }
    }


    public function testMinMax(BankSubEntites $bankSubEntites, Offer $offer): void
    {
        $bank = $bankSubEntites->bank;
        $minMaxDefault = [
            'ageMin' => 0,
            'ageMax' => 85,
            'timeMin' => 0,
            'timeMax' => 30,
            'min' => 0,
            'max' => 100000000,
            'minMSK' => 0,
            'maxMSK' => 100000000,
            'minSPB' => 0,
            'maxSPB' => 100000000,
            'minSoc' => 0,
            'maxSoc' => 100000000,
            'minSocMSK' => 0,
            'maxSocMSK' => 100000000,
            'minSocSPB' => 0,
            'maxSocSPB' => 100000000,
            'minFamily' => 0,
            'maxFamily' => 100000000,
            'minFamilyMSK' => 0,
            'maxFamilyMSK' => 100000000,
            'minFamilySPB' => 0,
            'maxFamilySPB' => 100000000,
            'minIt' => 0,
            'maxIt' => 100000000,
            'minItMSK' => 0,
            'maxItMSK' => 100000000,
            'minItSPB' => 0,
            'maxItSPB' => 100000000,
            'min2d' => 0,
            'max2d' => 100000000,
            'min2dMSK' => 0,
            'max2dMSK' => 100000000,
            'min2dSPB' => 0,
            'max2dSPB' => 100000000,
        ];
        $minMax = array_merge($minMaxDefault, array_filter($bank->getMinMax()));
        $calcData = $bank->calcData;

        // if ($offer->getAge() < $minMax['ageMin'] || $offer->getAge() > $minMax['ageMax']) {
        //     throw new \Exception("Возраст заёмщика должен быть в пределах: " . $minMax['ageMin'] . "-" . $minMax['ageMax'] . " лет.");
        // }

        if (intval($offer->time) < $minMax['timeMin'] * 12 || intval($offer->time) > $minMax['timeMax'] * 12) {
            throw new \Exception("Срок займа должен быть в пределах: " . $minMax['timeMin'] . "-" . $minMax['timeMax'] . " лет.");
        }

        if (
            $calcData['bodySumm'] < $minMax[$this->getSummKey($offer, 'min')] ||
            $calcData['bodySumm'] > $minMax[$this->getSummKey($offer, 'max')]
        ) {
            throw new \Exception(
                "Сумма кредита для указанных параметров должна быть в пределах: " .
                    number_format($minMax[$this->getSummKey($offer, 'min')], 0, ",", " ") . " - " .
                    number_format($minMax[$this->getSummKey($offer, 'max')], 0, ",", " ") . " ₽"
            );
        }

        try {
            $needFirstPayEntity = $this->getFirstPartEntity($bankSubEntites, $offer);
            $minFirtstPart = $this->getFirstPay($needFirstPayEntity, $offer);
        } catch (\Throwable $th) {
            throw new \Exception("Ошибка. Не удалось расчитать минимальный первоначальный взнос банка");
        }
        $firstPart = $offer->firstpay / (($calcData['bodySumm'] + $offer->firstpay) / 100);

        if ($firstPart < $minFirtstPart) {
            throw new \Exception("Первоначальный взнос слишком мал. Минимум: " . $minFirtstPart . "%, текущий: " . floor($firstPart) . '%');
        }
    }



    const bankOptionsEntity = [
        'ипотека' => 'getIpotekaOptions',
        'рефинансирование' => 'getRefinanceOptions',
        'залог' => 'getPledgeOptions',
    ];

    const bankCheckEntity = [
        'квартира' => 'getCheckFlat',
        'комната' => 'getCheckRoom',
        'отд_доля' => 'getCheckRoom',
        'дом' => 'getCheckHome',
        'участок' => 'getCheckHome',
        'таунхаус' => 'getCheckTynehouse',
        'апартаменты' => 'getCheckApartments',
        'кн' => 'getCheckKn',
        'ижс' => 'getCheckIjs',
    ];

    const bankNumFirstPart = [
        'квартира' => 'getFirstFlat',
        'комната' => 'getFirstRoom',
        'отд_доля' => 'getFirstRoom',
        'дом' => 'getFirstHome',
        'участок' => 'getFirstHome',
        'таунхаус' => 'getFirstTynehouse',
        'апартаменты' => 'getFirstApartments',
        'кн' => 'getFirstKn',
        'ижс' => 'getFirstIjs',
    ];

    const creditTargets = [
        'ипотека' => 'hasIpoteka',
        'рефинансирование' => 'hasRefinance',
        'залог' => 'hasPledge',
    ];

    const stateSuportEntity = [
        15664 => 'getProcSocial',
        15666 => 'getProcFamily',
        15668 => 'getProcWar',
    ];


    public function getSummKey(Offer $offer, string $mode)
    {
        $type = $mode; //$mode может быть либо 'min' либо 'max'
        $keyName = $type;
        $isMSK = (in_array($offer->getTown()->getTitle(), ["Московская область", "Москва"]));
        $isSPB = ($offer->getTown()->getTitle() === "Санкт-Петербург" || $offer->getTown()->getTitle() === "Ленинградская область");
        switch ($offer->getStateSupport()) {
            case 15664:
                $keyName .= 'Soc';
                break;
            case 15666:
                $keyName .= 'Family';
                break;
            case 15956:
                $keyName .= 'It';
                break;
        }
        if ($offer->getProofMoney() === 30) {
            $keyName = $type . '2d';
        }
        if ($isMSK) {
            $keyName .= 'MSK';
        }
        if ($isSPB) {
            $keyName .= 'SPB';
        }
        // dump($keyName);
        return $keyName;
    }

    public function getFirstPartEntity(BankSubEntites $bankSubEntites, Offer $offer): BankNum
    {
        $needBankOptions = $bankSubEntites->bankOptions;

        $stateSuportFirsts = [
            15664 => 'getFirstSocial',
            15666 => 'getFirstFamily',
            15668 => 'getFirstWar',
            15956 => 'getFirstIt'
        ];

        if ($offer->getStateSupport() !== 15662) {
            try {
                $needBankNum = $needBankOptions->{$stateSuportFirsts[$offer->getStateSupport()]}();
                $stateSupport = $offer->getStateSupport();
                if ($offer->getObjectType() === 'ижс' && $offer->getNationality() === Offer::NATIONALITY_TYPE['РФ'] && in_array($stateSupport, [15664, 15666, 15956])){
                    $needBankNum = $needBankOptions->getFirstIjsSocialFamilyIt();
                }
                return $needBankNum;
            } catch (\Throwable $th) {
                throw new \Exception("Ошибка. Не удалось расчитать ограничения минимального первоначального взноса по данной госпрограмме");
            }
        }


        $method = $this::bankNumFirstPart[$offer->getObjectType()];
        if ($offer->getSalerType() === 'застройщик' && $method === 'getFirstFlat')
            $method = 'getFirstFlatNew';
        $needBankNum = $needBankOptions->{$method}();
        return $needBankNum;
    }

    public function getFirstPay(BankNum $bankNum, Offer $offer): float
    {
        $fieldName = 'getNDFL';
        $proofOfMoney = $offer->getProofMoney();
        $nationality = $offer->getNationality();
        $stateSupport = $offer->getStateSupport();
        $objectType = $offer->getObjectType();

        /**
         * если тип работы не найм (ИП или Бизнес или Самозанятый), то используем значения ПВ из полей созданных отдельно для этих категорий
         */
        if ($offer->getHiringType() !== 10){
            if ($offer->getProofMoney() === 30){ //если по 2 документам, отдельные значения ПВ
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getBusiness2doc' : 'getMigrantBusiness2doc';
            } else {
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getBusiness' : 'getMigrantBusiness';
            }
            return $bankNum->$fieldName() ? $bankNum->$fieldName() : 0;
        }

        /**
         * Если выбрана семейная или льготная или военная ипотека, и тип жилья дом/таунхаус и подтверждение
         * по НДФЛ или форме банка, то выбирается отдельное ограничение по ПВ.
         * Иначе (если квартира или что-то ещё) берётся обычное поле из BankNum.
         */

        if ($objectType === 'дом' || $objectType === 'таунхаус') {
            if (in_array($stateSupport, [15664, 15666, 15956]) || $offer->getIsMilitaryMortgage()) {
                if (in_array($proofOfMoney, [10, 20])) {
                    $fieldName = 'getSupportHome';
                    return $bankNum->$fieldName() ? $bankNum->$fieldName() : 0;
                }
            }
            //Если по 2 документам семейная/льготная, то своё ограничение по ПВ у дома/таунхауса
            if (in_array($stateSupport, [15664, 15666])) {
                if (in_array($proofOfMoney, [30])) {
                    $fieldName = 'getSupportHome2doc';
                    return $bankNum->$fieldName() ? $bankNum->$fieldName() : 0;
                }
            }
        }

        switch ($proofOfMoney) {
            case 10:
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getNDFL' : 'getMigrantNDFL';
                break;
            case 20:
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getBankForm' : 'getMigrantBankForm';
                break;
            case 30:
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getOn2doc' : 'getMigrant2doc';
                break;
            case 40:
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getSZIILS' : 'getMigrantSZIILS';
                break;
            default:
                throw new \Exception('Неверный тип подтверждения дохода - ' . $proofOfMoney);
                break;
        }

        return $bankNum->$fieldName() ? $bankNum->$fieldName() : 0;
    }
}
