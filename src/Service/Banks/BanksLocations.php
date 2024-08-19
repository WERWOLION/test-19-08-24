<?php

namespace App\Service\Banks;

use App\Entity\Bank;
use Symfony\Component\Finder\Finder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;


//Связывает сущности банков и городов на основе excell файла
class BanksLocations
{

    public $excelFilePath;
    public $em;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em) {
        $this->excelFilePath =  __DIR__ . "\banks-city.xlsx";
        $this->em = $em;
    }

    /**
     * @return string
     */
    public function loadfile(string $cellsLetter)
    {
        $spreadsheet = IOFactory::load($this->excelFilePath);
        return $spreadsheet->getActiveSheet()->rangeToArray($cellsLetter . "2:" .$cellsLetter . "72", NULL, false, true, false);
    }

    public function loadCitiesList($towns)
    {
        $spreadsheet = IOFactory::load($this->excelFilePath);
        $allCities = $spreadsheet->getActiveSheet()->rangeToArray('A2:A72', NULL, false, true, false);
        $result = [];
        foreach ($allCities as $key => $value) {
            foreach ($towns as $town) {
                $townId = null;
                if( $town->getTitle() != $value[0] ) continue;
                $townId = $town;
                $result[$key]['town_entity'] = $townId;
                break;
            }
            $result[$key]['label'] = $value[0];
        }
        return $result;
    }



    public function AddTownList(Bank $bank, $dataset, $refTowns)
    {
        foreach ($dataset as $key => $info) {
            if($info[0] != "v") continue;
            if( isset( $refTowns[$key]['town_entity'] ) ){
                $townEntity = $refTowns[$key]['town_entity'];
                $bank->addTown($townEntity);
            }
        }
        return $bank;
    }


    
}