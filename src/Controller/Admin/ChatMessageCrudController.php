<?php

namespace App\Controller\Admin;

use App\Entity\ChatMessage;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ChatMessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ChatMessage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt', 'Дата')->hideWhenCreating(),
            AssociationField::new('user', 'Пользователь'),
            AssociationField::new('chatRoom', 'Чат'),
            TextareaField::new('content', 'Текст сообщения'),
        ];
    }
}
