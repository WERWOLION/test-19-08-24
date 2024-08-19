<?php
namespace App\Service\Calculator;

use App\Entity\BankMain;
use App\Entity\BankCheck;
use App\Entity\BankOption;

class BankSubEntites {
    public BankMain $bank;
    public BankOption $bankOptions;
    public BankCheck $bankCheck;
}
