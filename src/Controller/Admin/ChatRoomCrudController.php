<?php

namespace App\Controller\Admin;

use App\Entity\ChatRoom;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class ChatRoomCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChatRoom::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title' ,'Название чата'),
            AssociationField::new('user', "Владелец чата"),
            AssociationField::new('calculated', "Заявка"),
            Field::new('isOpen', 'Чат открыт'),
            Field::new('isGenericDialog', 'Чат техподдержки'),
            Field::new('fio', 'ФИО клиента'),
        ];
    }
}
