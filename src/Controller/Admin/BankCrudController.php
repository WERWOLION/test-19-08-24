<?php

namespace App\Controller\Admin;

use App\Entity\Bank;
use App\Entity\Attachment;
use App\Form\AttachmentType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BankCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Bank::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title', 'Заголовок'),
            Field::new('updatedAt', "Последнее обновление")->onlyOnIndex(),

            AssociationField::new('logo', "Логотип")->onlyOnForms(),

            Field::new('creditTargets', "Цели кредита")->onlyOnForms()->setHelp('Варианты: ипотека, рефинансирование, залог, материнский'),
            Field::new('objectTypes', "Тип недвижимости")->onlyOnForms()->setHelp('Варианты: "квартира", "дом", "комната", "апартаменты"'),
            Field::new('salerTypes', "Тип продавца")->onlyOnForms()->setHelp('Варианты: "физлицо", "застройщик", "юрлицо"'),
            Field::new('is2Doc', "Ипотека по 2 документам квартиры")->onlyOnForms(),
            Field::new('is2DocUnresident', "Ипотека по 2 документам, нерезидент")->onlyOnForms(),
            Field::new('is2DocRefinance', "Ипотека по 2 документам, рефинансирование")->onlyOnForms(),
            Field::new('isMotherCap', "Наличие Материнского капитала")->onlyOnForms(),
            Field::new('isWarCap', "Военная ипотека")->onlyOnForms(),
            Field::new('isSocial', "Льготная ипотека")->onlyOnForms(),
            Field::new('isFamily', "Семейная ипотека")->onlyOnForms(),
            Field::new('timeMin', "Min срок кредита, (лет)")->onlyOnForms(),
            Field::new('timeMax', "Max срок кредита, (лет)")->onlyOnForms(),
            Field::new('ageMin', "Min возраст, (лет)")->onlyOnForms(),
            Field::new('ageMax', "Max возраст, (лет)")->onlyOnForms(),

            FormField::addPanel('Ставки банка'),

            Field::new('procentStd', "Ставка стандартная"),
            Field::new('procentSocial', "Ставка льготная")->onlyOnForms(),
            Field::new('procentFamily', "Ставка семейная")->onlyOnForms(),
            Field::new('procent2Doc', "Ставка по 2 документам")->onlyOnForms(),
            Field::new('procentHouse', "Ставка Дом/Танхаус")->onlyOnForms(),
            Field::new('procentRoom', "Ставка Комната")->onlyOnForms(),
            Field::new('procentWar', "Ставка Военная ипотека")->onlyOnForms(),
            Field::new('procentPledge', "Под залог")->onlyOnForms(),
            Field::new('procentRefinance', "Ставка Рефинансирование")->onlyOnForms(),

            FormField::addPanel('Минимальные и максимальные суммы'),
            
            Field::new('min', "Минимальная станд.")->onlyOnForms()->setColumns(3),
            Field::new('max', "Максимальная станд.")->onlyOnForms()->setColumns(3),
            Field::new('minMSK', "Минимальная станд. МСК")->onlyOnForms()->setColumns(3),
            Field::new('maxMSK', "Максимальная станд. МСК")->onlyOnForms()->setColumns(3),

            Field::new('minSoc', "Минимальная льготная")->onlyOnForms()->setColumns(3),
            Field::new('maxSoc', "Максимальная льготная")->onlyOnForms()->setColumns(3),
            Field::new('minSocMSK', "Минимальная льготная МСК")->onlyOnForms()->setColumns(3),
            Field::new('maxSocMSK', "Максимальная льготная МСК")->onlyOnForms()->setColumns(3),

            Field::new('min2Doc', "Минимальная по 2 док.")->onlyOnForms()->setColumns(3),
            Field::new('max2Doc', "Максимальная по 2 док.")->onlyOnForms()->setColumns(3),
            Field::new('min2DocMSK', "Минимальная по 2 док. МСК")->onlyOnForms()->setColumns(3),
            Field::new('max2DocMSK', "Максимальная по 2 док. МСК")->onlyOnForms()->setColumns(3),

            FormField::addPanel('Первоначальные взносы'),

            Field::new('firstFlat', "ПВ квартира")->onlyOnForms()->setColumns(3),
            Field::new('firstHome', "ПВ дом/таунхаус")->onlyOnForms()->setColumns(3),
            Field::new('first2DocFlat', "ПВ квартира по 2 док.")->onlyOnForms()->setColumns(3),
            Field::new('first2DocUnresident', "ПВ по 2 док. квартира, нерезидент")->onlyOnForms()->setColumns(3),
            Field::new('first2DocRefinance', "ПВ по 2 док. квартира Рефинанс.")->onlyOnForms()->setColumns(3),
            Field::new('firstPledge', "ПВ под залог (квартира)")->onlyOnForms()->setColumns(3),
            Field::new('firstRefinance', "ПВ Рефинансирование")->onlyOnForms()->setColumns(3),
        ];
    }
}
