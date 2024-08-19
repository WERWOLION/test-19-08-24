<?php

namespace App\Controller\Admin;

use App\Entity\Slider;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\FileUploadType;

class SliderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Slider::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title', "Заголовок"),
            TextField::new('href', "Ссылка"),
            IntegerField::new('priority', 'Приоритет'),
            ImageField::new('image_desktop', "Изображение для десктопа (1366px - desktop)")
                ->setBasePath('uploads/sliders/')
                ->setUploadDir('public_html/uploads/sliders')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            ImageField::new('image_tablet', "Изображение для планшета (570px, но ниже, чем 1023px)")
                ->setBasePath('uploads/sliders/')
                ->setUploadDir('public_html/uploads/sliders')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            ImageField::new('image_phone', "Изображение для мобильного телефона (321px, но ниже, чем 569px)")
                ->setBasePath('uploads/sliders/')
                ->setUploadDir('public_html/uploads/sliders')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            ImageField::new('image_small_mobile', "Изображение для маленького мобильного телефона (320px)")
                ->setBasePath('uploads/sliders/')
                ->setUploadDir('public_html/uploads/sliders')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            ImageField::new('image_laptop', "Изображение для лаптопа (1024px, но ниже, чем 1365px)")
                ->setBasePath('uploads/sliders/')
                ->setUploadDir('public_html/uploads/sliders')
                ->setFormType(FileUploadType::class)
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            BooleanField::new('active'),
        ];
    }

}
