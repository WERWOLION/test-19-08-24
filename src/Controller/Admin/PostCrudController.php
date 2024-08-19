<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PostCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Post::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Страница')
            ->setEntityLabelInPlural('Страницы')
            ->setPageTitle('index', 'Все страницы')
            ->setPageTitle('new', 'Новая страница')
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('title', 'Заголовок'),
            Field::new('slug', 'Слаг страницы (латиница)'),
            Field::new('isPublish', 'Опубликована?'),
            Field::new('isAnon', 'Доступна без регистрации?'),
            TextareaField::new('body', 'Содержимое страницы')->setFormType(CKEditorType::class)->setFormTypeOptions([
                'config' => [
                    'height' => 500,
                ]
            ])->onlyOnForms()->setDefaultColumns('col-12'),
        ];
    }
}
