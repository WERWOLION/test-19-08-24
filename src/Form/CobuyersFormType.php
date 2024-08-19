<?php

namespace App\Form;

use App\Entity\Offer;
use App\Form\BuyerFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CobuyersFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('buyer', BuyerFormType::class, [
            'label' => false,
            'attr' => [
                'class' => 'row',
            ],
            'passport_mask' => $options['passport_mask'],
        ])
        ->add('cobuyers', CollectionType::class, [
            'attr' => [
                'class' => 'jsCanRemove',
            ],
            'entry_options' => [
                'allow_remove' => true,
            ],
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => BuyerFormType::class,
            'by_reference' => false,
            'mapped' => true,
            'entry_options' => [
                'remove_button' => true,
                'label' => false,
                'required' => true,
                'attr' => [
                    'class' => 'row',
                ],
                'passport_mask' => $options['passport_mask'],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'passport_mask' => true, //Показывать ли маску ввода на паспорт
        ]);
        $resolver->setAllowedTypes('passport_mask', 'bool');
    }
}
