<?php

namespace App\Controller\Admin;

use App\Entity\Sitesettings;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SitesettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sitesettings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Блок контента')
            ->setEntityLabelInPlural('Блоки контента')
            ->setPageTitle('index', 'Все блоки контента')
            ->setPageTitle('new', 'Новый блок контента')
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('title', 'Заголовок'),
            Field::new('label', 'Ярлык (латиница)'),
            TextareaField::new('settings', 'Содержимое')->setFormType(CKEditorType::class)->setFormTypeOptions([
                'config' => [
                    'height' => 500,
                ]
            ])->onlyOnForms()->setDefaultColumns('col-12'),
        ];
    }
}
