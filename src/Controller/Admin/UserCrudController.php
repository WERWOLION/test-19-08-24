<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Partner;
use App\Form\AdminHistoryFormType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{

    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Пользователь')
            ->setEntityLabelInPlural('Пользователи')
            ->setPageTitle('index', 'Все пользователи')
            ->setPageTitle('new', 'Новый пользователь')
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['id' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewPartner = Action::new('viewPartner', 'Просмотр')
            ->displayIf(static function ($entity) {
                return $entity->getPartner()?->getId();
            })
            ->linkToRoute('admin_partner_show', fn(User $user) => ['id' => $user->getPartner()?->getId()]);
        return $actions->add(Crud::PAGE_INDEX, $viewPartner);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
      $this->setUserPlainPassword($entityInstance);
      $entityManager->persist($entityInstance);
      $entityManager->flush();
    }

    private function setUserPlainPassword(User $user): void
    {
      if ($user->passChanger) {
        $user->setPassword($this->hasher->hashPassword($user, $user->passChanger));
      }
    }


    public function configureFields(string $pageName): iterable
    {
        $partner = $this->getContext()->getEntity()?->getInstance()?->getPartner();

        $mainFields =  [
            FormField::addPanel("Данные пользователя"),
            Field::new('id', "ID")->onlyOnIndex(),
            Field::new('lastname', "Фамилия")->setColumns(4)->onlyOnForms(),
            Field::new('firstname', "Имя")->setColumns(4)->onlyOnForms(),
            Field::new('middlename', "Отчество")->setColumns(4)->onlyOnForms(),
            EmailField::new('email', "Email")->setColumns(4),
            Field::new('fio', "ФИО")->onlyOnIndex(),
            Field::new('phone', "Номер телефона")->setColumns(4),
            TextField::new('passChanger', 'Новый пароль')->setFormType(PasswordType::class)->setFormTypeOptions([
                'attr' => [
                    'autocomplete' => 'new-password',
                ]
            ])->onlyOnForms()->setColumns(4),
            BooleanField::new('isEmailConfirm', "Активирован?"),
            AssociationField::new('town', "Город")->onlyOnForms()->setColumns(4),
            Field::new('bitrixManagerID', "Менеджерский ID Битрикс24")->onlyOnForms()->setColumns(4)->setHelp('ID пользователя Б24, если этот пользователь менеджер'),
            AssociationField::new('myManager', "Ответственный")->setColumns(4),
        ];

        $partnerFields = [
            FormField::addPanel("Данные партнера"),
            TextField::new('partner.fullname', "ФИО физлица/Название организации")->onlyOnForms()->setColumns(3),
            TextField::new('partner.contactface', "ФИО директора")->onlyOnForms()->setColumns(3),
            ChoiceField::new('partner.type', "Тип партнера")->setChoices(array_flip(Partner::PARTNER_TYPE))->onlyOnForms()->setColumns(3),
            TextField::new('partner.inn', "ИНН")->onlyOnForms()->setColumns(3),
            TextField::new('partner.postadress', "Почтовый адрес")->setColumns(6),
            TextField::new('partner.legaladress', "Юридический адрес")->setColumns(6),
            TextField::new("partner.bankname", "Наименование банка получателя")->onlyOnForms()->setColumns(3),
            TextField::new('partner.bankbik', "БИК")->onlyOnForms()->setColumns(3),
            TextField::new('partner.bankaccount', "Расчетный счет")->onlyOnForms()->setColumns(3),
            TextField::new('partner.bitrixContactID', "ID в Bitrix24")->setColumns(3),
            MoneyField::new('partner.totalsumm', "Сумма кредитов, руб.")->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ])->setColumns(3),
            MoneyField::new('wallet.balance', "Предварительнй реф. баланс, руб.")->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ])->setColumns(3),
            MoneyField::new('wallet.balanceReady', "Готово к выводу, руб.")->setCustomOptions([
                'currency' => 'RUB',
                'numDecimals' => 0,
            ])->setColumns(3),
            CollectionField::new('partner.bonusHistory', "История заявок")->onlyOnForms()
                ->allowAdd()
                ->allowDelete()
                ->setEntryIsComplex(true)
                ->setEntryType(AdminHistoryFormType::class)
                ->setCustomOption('renderExpanded', false),
        ];

        if($partner){
            return array_merge($mainFields, $partnerFields);
        }
        return $mainFields;
    }
}
