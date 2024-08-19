<?php

namespace App\Controller\Admin;

use App\Entity\Offer;
use App\Entity\Calculated;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CalculatedCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Calculated::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Заявка')
            ->setEntityLabelInPlural('Заявки')
            ->setPageTitle('index', 'Все заявки')
            ->setPageTitle('new', 'Новая заявка')
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status', 'Статус заявки')->setChoices(Offer::OFFER_STATUS))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            DateTimeField::new('createdAt', 'Дата создания')->onlyOnIndex(),
            TextField::new('bankName', 'Банк')->setFormTypeOption('property_path','other[bankName]')->setTemplatePath('adminka/_linkbank.html.twig')->setDisabled(true),
            ChoiceField::new('status', 'Статус заявки')->setChoices(
                Offer::OFFER_STATUS
            ),
            ChoiceField::new('offer.status', 'Статус родительской заявки')->setChoices(
                Offer::OFFER_STATUS
            )->onlyOnForms(),
            IntegerField::new('id', "Ссылка на заявку")->setTemplatePath('adminka/_linkfield.html.twig')->onlyOnIndex(),
            MoneyField::new('fullsumm', 'Запрошенная сумма')->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ]),
            MoneyField::new('firstpay', 'Первоначальный взнос')->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ])->onlyOnForms(),
            BooleanField::new('offer.isMotherCap', 'Наличие мат. капитала')->onlyOnForms(),
            Field::new('motherCapSize', 'Остаток мат. капитала')->onlyOnForms(),
            Field::new('monthcount', 'Число месяцев')->onlyOnForms(),
            Field::new('procent', 'Процент')->onlyOnForms(),
            Field::new('bitrixID', 'ID сделки в Битрикс24')->onlyOnForms(),
            MoneyField::new('truefullsumm', 'Выданная/одобренная сумма')->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ])->onlyOnForms(),
            Field::new('isPayDone', 'Бонус был выплачен?')->onlyOnForms(),
            AssociationField::new('offer', "Родительская заявка"),
        ];
    }
}
