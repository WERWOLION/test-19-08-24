<?php

namespace App\Controller\Admin;

use App\Entity\Wallet;
use App\Entity\Transaction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            DateTimeField::new('createdAt', 'Дата создания')->onlyOnIndex(),
            ChoiceField::new('type', 'Тип')->setChoices(Transaction::TRANSACTION_TYPES)->setDisabled(true),
            MoneyField::new('amount', 'Сумма')->setDisabled(true)->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ]),
            ChoiceField::new('status', 'Статус')->setChoices(Transaction::TRANSACTION_STATUS)->setDisabled(true),
            TextareaField::new('message', 'Комментарий'),
            AssociationField::new('reciverWallet', 'Получатель')->onlyOnForms()->setDisabled(true)
                ->setFormTypeOption('choice_label', function (Wallet $wallet) {
                    return $wallet->getUserAccount()?->getPartner()?->getId() . '/' .
                        $wallet->getUserAccount()?->getId() . " - " .
                        $wallet->getUserAccount()?->getFio();
                }),
        ];
    }
}
