<?php

namespace App\Controller\Admin;

use App\Entity\News;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class NewsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return News::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setPaginatorPageSize(300)
            ->setEntityLabelInSingular('Новость')
            ->setEntityLabelInPlural('Новости')
            ->setPageTitle('index', 'Все новости')
            ->setPageTitle('new', 'Новая новость')
            ->setPaginatorPageSize(30)
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title', "Заголовок"),
            TextareaField::new('content', 'Содержимое')->setFormType(CKEditorType::class)->setFormTypeOptions([
                'config' => [
                    'height' => 500,
                ]
            ])->onlyOnForms()->setDefaultColumns('col-12'),
            ImageField::new('image', "Изображение")
                ->setBasePath('uploads/news/')
                ->setUploadDir('public_html/uploads/news')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
        ];
    }
}
