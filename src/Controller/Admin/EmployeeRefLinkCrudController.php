<?php

namespace App\Controller\Admin;

use App\Entity\EmployeeRefLink;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EmployeeRefLinkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmployeeRefLink::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', 'Название'),
            TextField::new('hash', 'Хеш для ссылки, подставлять в https://lk.ipoteka.life/register?ref_hash=хеш'),
            TextField::new('fullHref', 'Ссылка'),
            Field::new('bitrix_id', 'ID который выставится в поле "ID для наблюдателя с сайта" в битриксе'),
            Field::new('agentId', 'ID агента'),
        ];
    }

}
