<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Attachment;
use App\Form\AttachmentType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Provider\FieldProvider;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class AttachmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Attachment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
      return $crud
        ->setPaginatorPageSize(100)
        ->setDefaultSort(['id' => 'DESC']);
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('id', "ID")->onlyOnIndex(),
            Field::new('foldername', "Название папки"),
            Field::new('description', "Описание"),
            TextareaField::new('file')->setFormType(VichImageType::class)->setFormTypeOptions(["allow_delete" => false]),
            AssociationField::new('user', "Владелец"),
        ];
    }
}
