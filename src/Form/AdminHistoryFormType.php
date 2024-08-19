<?php

namespace App\Form;

use App\Entity\Buyer;
use App\Form\BuyerFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class AdminHistoryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('bankTitle', null, [
                'row_attr' => [
                ],
                'label' => "Банк",
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('bank', null, [
                'row_attr' => [
                ],
                'label' => "ID банка",
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('summ', null, [
                'row_attr' => [
                ],
                'label' => "Сумма, ₽",
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('bonus', null, [
                'row_attr' => [
                ],
                'label' => "Бонус, ₽",
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // 'data_class' => Buyer::class,
        ]);
    }
}
