<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FeePercentType extends AbstractType
{
    public $title = 'item';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('feePercent', NumberType::class, ['label' =>'Процент комиссии, ипотека, %', 'required'   => true])
            ->add('basePercentChange', NumberType::class, ['label' =>'Изменение базовой ставки, %', 'required'   => true])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
