<?php
namespace App\Service\Calculator;

use App\Entity\Offer;
use App\Entity\BankMain;

//Расчётные функции для кредитов
class MathUtils
{

    public function __construct(
    ){}

    public function getAnnuityPayment( $body, $month, $percent_rate ){
        $partProc = $percent_rate / (100 * 12);
        $years_step = pow(1 + $partProc, -$month);
        $out = $body * ($partProc / (1 - $years_step));
        $out = round($out);
        return $out;
    }


    function getFullSummByMounthPay( $month_pay, $month_count, $percent_rate ){
        $years = $percent_rate / 100 / 12;
        $years_fix = 1 + $years;
        $years_step = pow($years_fix, $month_count);
        $body = $month_pay / ( $years + ( $years / ( $years_step - 1 ) ) );
        $body = round($body, 2);
        return $body;
    }

    function getFullSummByIncome( $month_pay, $month_count, $percent_rate ){
        $years = $percent_rate / 100 / 12;
        $years_fix = 1 + $years;
        $years_step = pow($years_fix, $month_count);
        $body = $month_pay / ( $years + ( $years / ( $years_step - 1 ) ) );
        $body = round($body, 2);
        return $body;
    }

    public function getFullCreditSumm( $monthCount, $monthPay ){
        return round($monthPay * $monthCount);
    }

    /**
     * Определяет сумму тела кредита в зависимости от "Расчёт по:"
     */
    public function calculateBodySummOfType(Offer $calcQuery, float $percent) : int
    {
        switch ($calcQuery->getCalcPriceType()) {
            case 20:
                $result = $this->getFullSummByMounthPay(
                    $calcQuery->cost,
                    $calcQuery->time,
                    $percent,
                );
                break;
            case 30:
                $result = $this->getFullSummByIncome(
                    $calcQuery->cost,
                    $calcQuery->time,
                    $percent,
                );
                break;
            default:
                $result = intval($calcQuery->cost - $calcQuery->firstpay);
                break;
        }
        return $result;
    }

    public function getRussianYearTitles($n, $titles) {
        $cases = array(2, 0, 1, 1, 1, 2);
        return $titles[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]];
        //echo getRussianYearTitles(631, array('яблоко', 'яблока', 'яблок'));
    }



}
