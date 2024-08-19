<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UpdatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'constraints' => [
                    new NotBlank(),
                ],
                'mapped' => false,
                'first_options' => [
                    'label' => 'Новый пароль',      
                ],
                'second_options' => [
                    'label' => 'Повтор пароля',      
                ],
                'invalid_message' => 'Поля паролей должны совпадать.',
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary btn-lg'),
                'label' => "Обновить пароль",
            ));
        ;
    }
}