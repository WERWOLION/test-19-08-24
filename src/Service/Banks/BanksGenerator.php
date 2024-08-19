<?php

namespace App\Service\Banks;

use App\Entity\Bank;
use Symfony\Component\Finder\Finder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;


//Генерирует сущности банков на основе excell файла
class BanksGenerator
{

    public $excelFilePath;
    public $em;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em) {
        $this->excelFilePath =  __DIR__ . "\banks.xlsx";
        $this->em = $em;
    }

    /**
     * @return string
     */
    public function loadfile()
    {
        $spreadsheet = IOFactory::load($this->excelFilePath);
        $allBanks = [];
        $result = $spreadsheet->getActiveSheet()->rangeToArray(
            'D1:K85',    // The worksheet range that we want to retrieve
            NULL,        // Value that should be returned for empty cells
            false,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
            true,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
            false         // Should the array be indexed by cell row and cell column
        );

        foreach (["C", "D", "E", "F", "G", "H", "I", "J", "K"] as $key => $value) {
            $allBanks[] = $spreadsheet->getActiveSheet()->rangeToArray($value . '1:' . $value .'85', NULL, false, true, false);
        }
        return $allBanks;
    }


    public function createBank(array $inp)
    {
        $bank = new Bank();
        $bank->setTitle($inp[0][0]);

        $creditTargets = [];
        $inp[2][0] == "v" ? $creditTargets[] = "ипотека" : null;
        $inp[3][0] == "v" ? $creditTargets[] = "рефинансирование" : null;
        $inp[4][0] == "v" ? $creditTargets[] = "залог" : null;
        $inp[5][0] == "v" ? $creditTargets[] = "материнский" : null;
        $bank->setCreditTargets($creditTargets);

        $objTypes = [];
        $inp[7][0] == "v" ? $objTypes[] = "квартира" : null;
        $inp[8][0] == "v" ? $objTypes[] = "дом" : null;
        $inp[9][0] == "v" ? $objTypes[] = "комната" : null;
        $inp[10][0] == "v" ? $objTypes[] = "апартаменты" : null;
        $bank->setObjectTypes($objTypes);

        $salerTypes = [];
        $inp[12][0] == "v" ? $salerTypes[] = "физлицо" : null;
        $inp[13][0] == "v" ? $salerTypes[] = "застройщик" : null;
        $inp[14][0] == "v" ? $salerTypes[] = "юрлицо" : null;
        $bank->setSalerTypes($salerTypes);

        $inp[16][0] == "v" ? $bank->setIs2Doc(true) : null;
        $inp[17][0] == "v" ? $bank->setIs2DocUnresident(true) : null;
        $inp[18][0] == "v" ? $bank->setIs2DocRefinance(true) : null;

        $inp[21][0] == "v" ? $bank->setIsMotherCap(true) : null;
        $inp[22][0] == "v" ? $bank->setIsWarCap(true) : null;
        $inp[25][0] == "v" ? $bank->setIsSocial(true) : null;
        $inp[27][0] == "v" ? $bank->setIsFamily(true) : null;

        $bank->setTimeMin(intval($inp[37][0]));
        $bank->setTimeMax(intval($inp[38][0]));

        $bank->setAgeMin(intval($inp[39][0]));
        $bank->setAgeMax(intval($inp[40][0]));

        $bank->setProcentStd($inp[45][0] === "нет" ? null : $inp[45][0]);
        $bank->setProcentSocial($inp[46][0] === "нет" ? null : $inp[46][0]);
        $bank->setProcentFamily($inp[47][0] === "нет" ? null : $inp[47][0]);
        $bank->setProcent2Doc($inp[48][0] === "нет" ? null : $inp[48][0]);
        $bank->setProcentHouse($inp[49][0] === "нет" ? null : $inp[49][0]);
        $bank->setProcentRoom($inp[50][0] === "нет" ? null : $inp[50][0]);
        $bank->setProcentRefinance($inp[51][0] === "нет" ? null : $inp[51][0]);
        $bank->setProcentWar($inp[52][0] === "нет" ? null : $inp[52][0]);
        $bank->setProcentPledge($inp[53][0] === "нет" ? null : $inp[53][0]);


        $bank->setMin($inp[59][0]);
        $bank->setMinMSK($inp[60][0]);
        $bank->setMax($inp[61][0]);
        $bank->setMaxMSK($inp[62][0]);

        $bank->setMinSoc($inp[64][0]);
        $bank->setMinSoc($inp[65][0]);
        $bank->setMaxSoc($inp[66][0]);
        $bank->setMaxSocMSK($inp[67][0]);

        $bank->setMin2Doc($inp[69][0] === "нет" ? null : $inp[69][0]);
        $bank->setMin2DocMSK($inp[70][0] === "нет" ? null : $inp[70][0]);
        $bank->setMax2Doc($inp[71][0] === "нет" ? null : $inp[71][0]);
        $bank->setMax2DocMSK($inp[72][0] === "нет" ? null : $inp[72][0]);

        $bank->setFirstFlat($inp[75][0] === "нет" ? null : $inp[75][0]);
        $bank->setFirstMother(intval($inp[76][0]));
        $bank->setFirst2DocFlat($inp[77][0] === "нет" ? null : $inp[77][0]);
        $bank->setFirst2DocUnresident($inp[78][0] === "нет" ? null : $inp[78][0]);
        $bank->setFirst2DocRefinance($inp[79][0] === "нет" ? null : $inp[79][0]);
        $bank->setFirstHome($inp[80][0] === "нет" ? null : $inp[80][0]);
        $bank->setFirstPledge($inp[81][0] === "нет" ? null : $inp[81][0]);
        $bank->setFirstRefinance($inp[82][0] === "нет" ? null : $inp[82][0]);

        $this->em->persist($bank);
        $this->em->flush();
        return $bank;
    }
}
