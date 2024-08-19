<?php

namespace App\Service\Calculator;

use App\Entity\Offer;
use App\Entity\BankNum;
use App\Entity\BankMain;
use App\Service\Calculator\BankSubEntites;

class PercentCalculator
{
    public function calcBankPercent(BankSubEntites $bankEntities, Offer $offer): float
    {
        //Сущность - определенная цель кредита
        $needBankOptions = $bankEntities->bankOptions;

        $objectType = $offer->getObjectType();
        $stateSuport = $offer->getStateSupport();
        if ($offer->getIsMilitaryMortgage()) {
            $stateSuport = 15668;
        }
        $objectTypeNumEntity = [
            'квартира' => $offer->getSalerType() === 'застройщик' ? 'getProcFlatNew' : 'getProcFlat',
            'комната' => 'getProcRoom',
            'отд_доля' => 'getProcRoom',
            'дом' => 'getProcHome',
            'участок' => 'getProcHome',
            'таунхаус' => 'getProcTynehouse',
            'апартаменты' => 'getProcApartments',
            'кн' => 'getProcKn',
            'ижс' => 'getProcIjs',
        ];

        $stateSuportEntity = [
            15664 => 'getProcSocial',
            15666 => 'getProcFamily',
            15668 => 'getProcWar',
            15956 => 'getProcIt',
        ];

        if (!isset($objectTypeNumEntity[$objectType])) {
            throw new \Error('Ошбика. Не определен тип недвижимости или в калькуляторе произошла ошибка');
        }

        if ($stateSuport === 15662) {
            //без гос поддержки, проценты в зависимости от типа жилья
            if (!isset($objectTypeNumEntity[$objectType])) throw new \Error('Ошибка. Неизвестный тип недвижимости');
            $needBankNum = $needBankOptions->{$objectTypeNumEntity[$objectType]}();
        } else {
            //с господдержкой, проценты в зависимости от типа господдержки
            if (!isset($stateSuportEntity[$stateSuport])) throw new \Error('Ошибка. Неизвестный тип господдержки');
            $needBankNum = $needBankOptions->{$stateSuportEntity[$stateSuport]}();
            if ($offer->getObjectType() === 'ижс' && $offer->getNationality() === Offer::NATIONALITY_TYPE['РФ'] && in_array($stateSuport, [15664, 15666, 15956])){
                switch ($stateSuport){
                    case 15664:
                        $needBankNum = $needBankOptions->getProcIjsSocial();
                        break;
                    case 15666:
                        $needBankNum = $needBankOptions->getProcIjsFamily();
                        break;
                    case 15956:
                        $needBankNum = $needBankOptions->getProcIjsIt();
                        break;
                }
            }
        }

        $resultPercent = $this->getBankNumValue($needBankNum, $offer);
        if (!$resultPercent) throw new \Exception('Банк не выдает кредит, для указанных вами параметров фильтра');
        return $resultPercent;
    }


    public function getBankNumValue(BankNum $bankNum, Offer $offer)
    {
        $fieldName = 'getNDFL';
        $proofOfMoney = $offer->getProofMoney();
        $nationality = $offer->getNationality();
        $hiringType = $offer->getHiringType();

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
            case 50:
            case 60:
            case 70:
            case 80:
                break;
            default:
                throw new \Exception('Неверный тип подтверждения дохода - ' . $proofOfMoney);
                break;
        }
        /**
         * если тип работы не найм (ИП или Бизнес или Самозанятый), то используем значения ставки из полей созданных отдельно для этих категорий
         */
        if ($hiringType !== 10){
            if ($offer->getProofMoney() === 30){
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getOn2doc' : 'getMigrant2doc';
            } else {
                $fieldName = $nationality === Offer::NATIONALITY_TYPE['РФ'] ? 'getBusiness' : 'getMigrantBusiness';
            }
        }
        return $bankNum->$fieldName();
    }
}
