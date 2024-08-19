<?php

namespace App\Controller\Admin;

use App\Entity\Town;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TownCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Town::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPaginatorPageSize(300);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            Field::new('isActive', "Включен?"),
            Field::new('isForCalc', "Показывать в калькуляторе?")
        ];
    }
}
