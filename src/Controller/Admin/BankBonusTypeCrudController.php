<?php

namespace App\Controller\Admin;

use App\Entity\BankBonusType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BankBonusTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BankBonusType::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', 'Название КВ'),
            TextField::new('slug', 'Уникальный идентификатор для PHP. К примеру: alpha_base_gotovoe_jile_ms_spb'),
            Field::new('percent', 'Процент'),
            AssociationField::new('bank', 'Банк')->renderAsNativeWidget(),

        ];
    }

}
