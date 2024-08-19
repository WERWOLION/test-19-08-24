<?php

namespace App\Service\Banks;


//Расчётные функции для кредитов
class BanksMath
{

    public function getAnnuityPayment( $body, $month, $percent_rate ){
        $partProc = $percent_rate / 100 / 12;
        $years_step = pow(1 + $partProc, $month);
        $out = $body * ( $partProc + ( $partProc / ( $years_step - 1 ) ) );
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




}
