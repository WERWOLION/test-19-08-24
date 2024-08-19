<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Wallet;
use App\Entity\MoneyRequest;
use Symfony\Component\Form\FormInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class MoneyRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MoneyRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Выплата')
            ->setEntityLabelInPlural('Выплаты')
            ->setPageTitle('index', 'Выплаты')
            ->setPageTitle('new', 'Новая выплата')
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        /**
         * @var MoneyRequest $money
         */
        $money = $entityDto->getInstance();
        $money->setAuthor($this->getUser());
        $money->setCreatedAt(new \DateTimeImmutable());
        return $this->createNewFormBuilder($entityDto, $formOptions, $context)->getForm();
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('wallet', 'Кому выполнить платеж')
                ->setFormTypeOption('choice_label', function(Wallet $wallet) {
                    return $wallet->getUserAccount()?->getPartner()?->getId() . '/' .
                    $wallet->getUserAccount()?->getId() . " - " .
                    $wallet->getUserAccount()?->getFio();
                })
                ->setTemplatePath('adminka/_linkwallet.html.twig')
                ->setHelp('ВАЖНО! Обязательно выберите партнера!')
                ->setFormTypeOptions([
                    'required' => true,
                    'placeholder' => 'Выберите партнера...',
                ])->hideWhenUpdating(),
            NumberField::new('amount', 'Введите сумму, ₽')->hideWhenUpdating(),
            DateTimeField::new('createdAt', 'Дата платежа'),
            ChoiceField::new('status', 'Статус платежа')->setChoices(MoneyRequest::WITHDRAWALS_STATUS)->hideWhenUpdating(),
            TextareaField::new('destination', 'Служебная информация')->setHelp('Видит только админ')->onlyOnForms()->setRequired(false),
            TextareaField::new('message', 'Комментарий')->onlyOnForms(),
            AssociationField::new('author', 'Кто создал платеж')->setFormTypeOption('choice_label', function(User $user) {
                return $user->getFio();
            })->setFormTypeOption('disabled', true),
        ];
    }
}
