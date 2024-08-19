<?php

namespace App\Service\Banks;

use App\Entity\Bank;
use App\Entity\Offer;
use App\Entity\Calculated;
use App\Service\Banks\BanksMath;
use App\Repository\BankRepository;



//Фильтрует банки, выводит сообщения об ошибках
class BanksCalculator
{

    public $banks;
    public $math;
    public $is2Doc;
    public $bodySum;

    public function __construct(BankRepository $banksRep, BanksMath $banksMath)
    {
        $this->banks = $banksRep->findAll();
        $this->math = $banksMath;
    }

    public function calculateBodySummOfType($calcQuery, $bank)
    {
        //Определяем сумму тела кредита в зависимости от "Расчёт по:"
        switch ($calcQuery->getCalcPriceType()) {
            case 10:
                $result = intval($calcQuery->cost - $calcQuery->firstpay);
                break;
            case 20:
                $result = $this->math->getFullSummByMounthPay(
                    $calcQuery->cost,
                    $calcQuery->time,
                    $this->calcBankPercent($bank, $calcQuery)
                );
                break;
            case 30:
                $result = $this->math->getFullSummByIncome(
                    $calcQuery->cost,
                    $calcQuery->time,
                    $this->calcBankPercent($bank, $calcQuery)
                );
                break;
            default:
                $result = intval($calcQuery->cost - $calcQuery->firstpay);
                break;
        }
        return $result;
    }


    public function testBank(Bank $bank, $calcQuery)
    {
        $bank->isAccept = true;
        $bank->errorMess = true;
        $bank->calcData = [];
        $this->bodySum = $this->calculateBodySummOfType($calcQuery, $bank);

        $isMKS = ($calcQuery->getTown()->getTitle() === "Московская область");
        $this->is2Doc = ($calcQuery->getProofMoney() === 30);

        //Сравнение региона
        if (!$bank->getTowns()->contains($calcQuery->getTown())) {
            $bank->isAccept = false;
            $bank->errorMess = "Мы не работаем с банком в регионе - " . $calcQuery->getTown()->getTitle();
            return;
        }

        //Сравнение цели кредита
        if (!in_array($calcQuery->getCreditTarget(), $bank->getCreditTargets())) {
            $bank->isAccept = false;
            $bank->errorMess = "Банк не предоставляет выбранную цель кредита";
            return;
        }

        //Сравнение типа продавца
        if (!in_array($calcQuery->getSalerType(), $bank->getSalerTypes())) {
            $bank->isAccept = false;
            $bank->errorMess = "Банк не кредитует выбранный тип продавца недвижимости";
            return;
        }

        //Сравнение военной ипотеки
        if ($calcQuery->getStateSupport() === Offer::STATESUPPORT_TYPE['Военная ипотека'] && !$bank->getIsWarCap()) {
            $bank->isAccept = false;
            $bank->errorMess = "Банк не работает с военной ипотекой";
            return;
        }

        //Можно ли по 2 докуметам
        if ($this->is2Doc && !$bank->getIs2Doc()) {
            $bank->isAccept = false;
            $bank->errorMess = "Банк не выдает кредит без подтверждения доходов (по 2 документам)";
            return;
        }
        if ($this->is2Doc && !$bank->getIs2DocRefinance() && $calcQuery->getCreditTarget() === "рефинансирование") {
            $bank->isAccept = false;
            $bank->errorMess = "В банке нельзя получить рефинансирование по двум документам";
            return;
        }

        //Ограничение макс суммы
        $maxSumm = $bank->getMax();
        if ($this->is2Doc) {
            $maxSumm = $bank->getMax2Doc();
        }
        if ($isMKS) {
            $maxSumm = $bank->getMaxMSK();
            if ($this->is2Doc) {
                $maxSumm = $bank->getMax2DocMSK();
            }
        }
        if ($this->bodySum > $maxSumm) {
            $bank->isAccept = false;
            $bank->errorMess = "Слишком большая сумма кредита. Максимум - " . number_format($maxSumm, 0, ",", " ") . " ₽";
            return;
        }

        //Ограничение мин суммы
        $minSumm = $bank->getMin();
        if ($this->is2Doc) {
            $minSumm = $bank->getMin2Doc();
        }
        if ($isMKS) {
            $minSumm = $bank->getMinMSK();
            if ($this->is2Doc) {
                $minSumm = $bank->getMin2DocMSK();
            }
        }
        if ($this->bodySum < $minSumm) {
            $bank->isAccept = false;
            $bank->errorMess = "Слишком маленькая сумма кредита. Минимум - " . number_format($minSumm, 0, ",", " ") . " ₽";
            return;
        }

        //Сравнение минимального Первоначального взноса
        if ($calcQuery->firstpay / ($calcQuery->cost / 100) < $this->getNeededFirstPay($bank, $calcQuery)) {
            $bank->isAccept = false;
            $bank->errorMess = "Первоначальный взнос слишком мал. Минимум: " . $this->getNeededFirstPay($bank, $calcQuery) . "%";
            return;
        }


        //Сравнение типа недвижимости
        if (!in_array($calcQuery->getObjectType(), $bank->getObjectTypes())) {
            $bank->isAccept = false;
            $bank->errorMess = "Банк не даёт кредит для данного типа недвижимости";
            return;
        }

        //Сравнение возраста клиента
        if (($calcQuery->getAge() < $bank->getAgeMin()) || ($calcQuery->getAge() > $bank->getAgeMax())) {
            $bank->isAccept = false;
            $bank->errorMess = "Возраст заёмщика должен быть в пределах: " . $bank->getAgeMin() . "-" . $bank->getAgeMax() . " лет.";
            return;
        }

        //Сравнение гражданства
        if ($this->is2Doc && $calcQuery->getNationality() !== 10) {
            if (!$bank->getIs2DocUnresident()) {
                $bank->isAccept = false;
                $bank->errorMess = "Нерезиденты не кредитуются по 2 документам в этом банке";
                return;
            }
        }

        //Сравнение срока кредита
        if ($calcQuery->getObjectType() != "кн") {
            if (($calcQuery->time / 12 < $bank->getTimeMin()) || ($calcQuery->time / 12 > $bank->getTimeMax())) {
                $bank->isAccept = false;
                $bank->errorMess = "Неподходящий срок кредита. Кредит выдаётся на срок " . $bank->getTimeMin() . "-" . $bank->getTimeMax() . " лет";
                return;
            }
        } else {
            if (($calcQuery->time / 12 < $bank->getTimeKNMin()) || ($calcQuery->time / 12 > $bank->getTimeKNMax())) {
                $bank->isAccept = false;
                $bank->errorMess = "Неподходящий срок кредита. Кредит выдаётся на срок " . $bank->getTimeKNMin() . "-" . $bank->getTimeKNMax() . " лет";
                return;
            }
        }

        if ($calcQuery->getObjectType() == "кн" && $calcQuery->getIsMotherCap()) {
            $bank->isAccept = false;
            $bank->errorMess = "При приобретении данного типа недвижимости нельзя использовать средства Материнского (семейного) капитала.";
            return;
        }


        $bank->calcData = $this->calcBankValues($bank, $calcQuery);
    }


    public function getBanksResult(Offer $calcQuery)
    {

        foreach ($this->banks as $bank) {
            $this->testBank($bank, $calcQuery);
        } //foreach

        $sortedBanks = uasort($this->banks, function ($a, $b) {
            if ($a->isAccept && $b->isAccept) return 0;
            if (!$a->isAccept && $b->isAccept) return 1;
            if ($a->isAccept && !$b->isAccept) return -1;
            return 0;
        });

        return $this->banks;
    }



    //Расчёт минимального размера Первоначального взноса по ипотеке
    public function getNeededFirstPay($bank, Offer $calcQuery)
    {
        $banksMinFirst = $bank->getFirstFlat();

        $banksMinFirst = $bank->getFirstFlat();
        if ($calcQuery->getObjectType() === "дом") {
            $banksMinFirst = $bank->getFirstHome();
        }

        if ($calcQuery->getCreditTarget() === "залог") {
            $banksMinFirst = $bank->getFirstPledge();
        }

        if ($calcQuery->getIsMotherCap()) {
            $banksMinFirst = $bank->getFirstMother();
        }

        if ($this->is2Doc) {
            $banksMinFirst = $bank->getFirst2DocFlat();
            if ($calcQuery->getCreditTarget() === "рефинансирование") {
                $banksMinFirst = $bank->getFirst2DocRefinance();
            }
            if ($calcQuery->getNationality() !== 10) {
                $banksMinFirst = $bank->getFirst2DocUnresident();
            }
        }

        if ($calcQuery->getCreditTarget() === "рефинансирование") {
            $banksMinFirst = $bank->getFirstRefinance();
        }

        return $banksMinFirst;
    }




    public function getRussianYearTitles($n, $titles)
    {
        $cases = array(2, 0, 1, 1, 1, 2);
        return $titles[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]];
        //echo getRussianYearTitles(631, array('яблоко', 'яблока', 'яблок'));
    }




    public function calcBankValues($bank, Offer $calcQuery)
    {
        $calcData = [
            // 'rate' => $bank->getProcentStd(),
            'rate' => $this->calcBankPercent($bank, $calcQuery),
            'monthlyPayment' => 0,
            'creditPart' => 0,
            'bodySumm' => $this->bodySum,
            'creditYear' => 0,
            'creditYearTxt' => "лет",
            'creditMonth' => $calcQuery->time,
        ];

        $calcData['monthlyPayment'] = $this->math->getAnnuityPayment(
            $this->bodySum,
            $calcQuery->time,
            $calcData['rate']
        );

        $calcData['creditPart'] = $this->math->getFullCreditSumm(
            $calcQuery->time,
            $calcData['monthlyPayment'],
        ) - $calcData['bodySumm'];

        if ($calcQuery->time >= 12) {
            $calcData['creditYear'] = intdiv($calcQuery->time, 12);
            $calcData['creditYearTxt'] = $this->getRussianYearTitles($calcData['creditYear'], ['год', 'года', 'лет']);
            $calcData['creditMonth'] = $calcQuery->time % 12;
        }

        return $calcData;
    }




    public function calcBankPercent(Bank $bank, Offer $offer)
    {
        $result = $bank->getProcentStd();

        //Если военная ипотека
        if ($offer->getStateSupport() === Offer::STATESUPPORT_TYPE['Военная ипотека'] && $bank->getProcentWar()) {
            return $bank->getProcentWar();
        }

        //Если деньги под залог
        if ($offer->getCreditTarget() === "залог" && $bank->getProcentPledge()) {
            return $bank->getProcentPledge();
        }

        //Если по двум документам
        if ($offer->getProofMoney() === 30 && $bank->getProcent2Doc()) {
            return $bank->getProcent2Doc();
        }

        //Если дом
        if ($offer->getObjectType() === "дом" && $bank->getProcentHouse()) {
            return $bank->getProcentHouse();
        }

        //Если комната
        if ($offer->getObjectType() === "комната" && $bank->getProcentRoom()) {
            return $bank->getProcentRoom();
        }

        //Если рефинансирование
        if ($offer->getCreditTarget() === "рефинансирование" && $bank->getProcentRefinance()) {
            return $bank->getProcentRefinance();
        }
        return $result;
    }



    public function calcBonus(Calculated $calculated, Bank $bank)
    {
        $procent = 0.6;
        $fullSumm = $calculated->getTruefullsumm() ? $calculated->getTruefullsumm() : $calculated->getFullsumm();
        if ($bank->getTitle() === "Транскапиталбанк") {
            $fullSumm = 0;
        }
        return round($fullSumm * $procent / 100);
    }
}
