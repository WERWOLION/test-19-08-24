<?php

namespace App\Service\Calculator;

use App\Entity\Offer;
use App\Entity\BankMain;
use App\Service\Calculator\MathUtils;
use App\Repository\BankMainRepository;
use App\Service\Calculator\BankTester;

class CalculatorService
{
    private $banksList;

    public function __construct(
        private BankMainRepository $banksRep,
        private MathUtils $mathUtils,
        private PercentCalculator $percentCalculator,
        private BankTester $bankTester,
        private int $bodysumm = 0,
    ){
        $bankAll = $this->banksRep->findAll();
        $this->banksList = array_filter($bankAll, fn(BankMain $bankMain) => $bankMain->getOther()['isOn']);
    }

    public function testBank(BankMain $bankMain, Offer $offer): BankMain
    {
        //Тестируем ограничения банка (галочки)
        try {
            $bankEntites = $this->bankTester->testAcceptBank($bankMain, $offer);
        } catch (\Throwable $th) {
            $bankMain->isAccept = false;
            $bankMain->errorMess = $th->getMessage();
            return $bankMain;
        }

        //Считаем процентную ставку в зависимости от условий
        try {
            $percent = $this->percentCalculator->calcBankPercent($bankEntites, $offer);
        } catch (\Throwable $th) {
            $bankMain->isAccept = false;
            $bankMain->errorMess = $th->getMessage();
            return $bankMain;
        }

        //Высчитываем сумму тела кредита в зависимости от "Расчёт по:"
        $bodySumm = $this->mathUtils->calculateBodySummOfType($offer, $percent);
        $bankMain->calcData['rate'] = $percent;
        $bankMain->calcData['bodySumm'] = $bodySumm;

        //Сравнимаем с минимальными-максимальными значениями банка
        try {
            $this->bankTester->testMinMax($bankEntites, $offer);
        } catch (\Throwable $th) {
            $bankMain->isAccept = false;
            $bankMain->errorMess = $th->getMessage();
            return $bankMain;
        }

        //Расчитываем ежемесячный платеж, переплату, и срок текстом
        $bankMain = $this->calcBankValues($bankMain, $offer);

        // Динамические ставки (рассчитываем все то же что и для $bankMain->calcData)
        if ($offer->getCreditTarget() === 'ипотека'){
            if ($offer->getStateSupport() === 15662 && $bankMain->isMarketIpotekaEnabled() && !empty($bankMain->getBonusProcentDinamic())){
                //рыночная ипотека
                foreach ($bankMain->getBonusProcentDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            } else if (in_array($offer->getStateSupport(), [15666, 15664, 15956]) && $bankMain->isStateIpotekaEnabled() && !empty($bankMain->getBonusProcentDinamic())){
                // льготная, семейная, ИТ
                foreach ($bankMain->getBonusProcentDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            }
        } else if ($offer->getCreditTarget() === 'рефинансирование') {
            if ($offer->getStateSupport() === 15662 && $bankMain->isMarketIpotekaRefEnabled() && !empty($bankMain->getStateProcentDinamic())){
                //рефинансирование
                foreach ($bankMain->getStateProcentDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            } else if (in_array($offer->getStateSupport(), [15666, 15664, 15956]) && $bankMain->isStateIpotekaRefEnabled() && !empty($bankMain->getStateProcentDinamic())){
                // рефинансирование с гос. поддержкой
                foreach ($bankMain->getStateProcentDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            }
        } else if ($offer->getCreditTarget() === 'залог'){
            if ($offer->getStateSupport() === 15662 && $bankMain->isMarketIpotekaPledgeEnabled() && !empty($bankMain->getBonusPledgeDinamic())){
                //залог
                foreach ($bankMain->getBonusPledgeDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            } else if (in_array($offer->getStateSupport(), [15666, 15664, 15956]) && $bankMain->isMarketIpotekaPledgeEnabled() && !empty($bankMain->getBonusPledgeDinamic())){
                // залог с гос. поддержкой
                foreach ($bankMain->getBonusPledgeDinamic() as $dinamicRate){
                    $this->fillCalcDinamic($bankMain, $offer, $dinamicRate);
                }
            }
        }

        return $bankMain;
    }

    public function fillCalcDinamic($bankMain, $offer, $dinamicRate)
    {
        $calcDataDinamic = [
                        'bodySumm' => $bankMain->calcData['bodySumm'],
                        'rate' => $bankMain->calcData['rate'] + $dinamicRate['basePercentChange'],
                    ];
        $calcDataDinamic['creditMonth'] = $offer->time; // число месяцев.
        $calcDataDinamic['monthlyPayment'] = $this->mathUtils->getAnnuityPayment($calcDataDinamic['bodySumm'], $offer->time, $calcDataDinamic['rate']);
        //Переплата
        $calcDataDinamic['creditPart'] = ($offer->time * $calcDataDinamic['monthlyPayment']) - $calcDataDinamic['bodySumm'];
        if($offer->time >= 12){
            $calcDataDinamic['creditYear'] = intdiv($offer->time, 12);
            $calcDataDinamic['creditYearTxt'] = $this->mathUtils->getRussianYearTitles($calcDataDinamic['creditYear'], ['год', 'года', 'лет']);
            $calcDataDinamic['creditMonth'] = $offer->time % 12;
        }
        $calcDataDinamic['feePercent'] = $dinamicRate['feePercent'];
        $calcDataDinamic['count'] = count($bankMain->calcDataDinamic)+1;
        $bankMain->calcDataDinamic[] = $calcDataDinamic;
    }


    public function calcBankValues(BankMain $bank, Offer $offer) : BankMain
    {
        $calcData = $bank->calcData;
        $calcData['creditMonth'] = $offer->time; // число месяцев.

        $calcData['monthlyPayment'] = $this->mathUtils->getAnnuityPayment(
            $calcData['bodySumm'],
            $offer->time,
            $calcData['rate']
        );

        //Переплата
        $calcData['creditPart'] = ($offer->time * $calcData['monthlyPayment']) - $calcData['bodySumm'];

        if($offer->time >= 12){
            $calcData['creditYear'] = intdiv($offer->time, 12);
            $calcData['creditYearTxt'] = $this->mathUtils->getRussianYearTitles($calcData['creditYear'], ['год', 'года', 'лет']);
            $calcData['creditMonth'] = $offer->time % 12;
        }

        $bank->calcData = $calcData;
        return $bank;
    }


    public function getBanksResult(Offer $offer)
    {
        foreach ($this->banksList as $bank) {
            $this->testBank($bank, $offer);
        }
        uasort($this->banksList, function ($a, $b) {
            if ($a->isAccept && $b->isAccept) return 0;
            if (!$a->isAccept && $b->isAccept) return 1;
            if ($a->isAccept && !$b->isAccept) return -1;
            return 0;
        });
        return $this->banksList;
    }
}
