<?php

namespace App\Form;

use App\Entity\Partner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PartnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', HiddenType::class, array(
                'data' => $options['partner_type'],
                'required' => true,
            ));


        if ($options['partner_type'] === 1 || $options['partner_type'] === 2) {
            $builder
                ->add('inn', null, [
                    'label' => "ИНН",
                    'attr' => [
                        'placeholder' => "Введите ваш ИНН",
                        'autocomplete' => "off",
                        'class' => 'jsDadataInn',
                        'data-masktype' => 'inn',
                        'data-mask' => 1,
                    ],
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true,
                ])
                ->add('fullname', null, [
                    'label' => "Ф.И.О.",
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true,
                ]);
        }

        if ($options['partner_type'] === 3) {
            $builder
                ->add('inn', null, [
                    'label' => "ИНН",
                    'constraints' => [
                        new NotBlank()
                    ],
                    'attr' => [
                        'placeholder' => "Введите ваш ИНН",
                        'autocomplete' => "off",
                        'class' => 'jsDadataInn',
                        'data-masktype' => 'inn',
                        'data-mask' => 1,
                    ],
                    'required' => true,
                ])
                ->add('fullname', null, [
                    'label' => "Наименование",
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true,
                ])
                ->add('nalogtype', ChoiceType::class, [
                    'label' => "Ваша система налогообложения",
                    'choices'  => [
                        'Общая (ОСНО)' => 'osno',
                        'Упрощенная (УСН)' => 'usn',
                    ],
                    'attr'  =>  [
                        'class' => 'form-select',
                    ],
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true,
                ]);
        }

        if ($options['partner_type'] === 4) {
            $builder
                ->add('inn', null, [
                    'label' => "ИНН",
                    'constraints' => [
                        new NotBlank()
                    ],
                    'attr' => [
                        'placeholder' => "Введите ваш ИНН",
                        'autocomplete' => "off",
                        'class' => 'jsDadataInn',
                        'data-masktype' => 'inn',
                        'data-mask' => 1,
                    ],
                ])
                ->add('fullname', null, [
                    'label' => "Наименование организации",
                    'constraints' => [
                        new NotBlank()
                    ],
                ])
                ->add('legaladress', null, [
                    'label' => "Юридический адрес",
                    'constraints' => [
                        new NotBlank()
                    ],
                ])
                ->add('ogrn', null, [
                    'label' => "КПП",
                    'constraints' => [
                        new NotBlank()
                    ],
                ])
                ->add('contactface', null, [
                    'label' => "Ф.И.О директора",
                    'constraints' => [
                        new NotBlank()
                    ],
                ])
                ->add('nalogtype', ChoiceType::class, [
                    'label' => "Ваша система налогообложения",
                    'choices'  => [
                        'Общая (ОСНО)' => 'osno',
                        'Упрощенная (УСН)' => 'usn',
                    ],
                    'attr'  =>  [
                        'class' => 'form-select',
                    ],
                ]);
        }

        $builder->add(
            $builder->create('pay_data', FormType::class, [
                'inherit_data' => true,
                'label' => 'Платежные реквизиты',
                'label_attr'  =>  [
                    'class' => 'subform__ttl',
                ],
                'attr'  =>  [
                    'class' => 'subform',
                ],
            ])
                ->add('bankname', null, [
                    'label' => "Наименование банка получателя",
                ])
                ->add('bankbik', null, [
                    'label' => "БИК",
                    'attr' => [
                        'data-masktype' => 'bank_bik',
                        'data-mask' => 1,
                    ]
                ])
                ->add('bankaccount', null, [
                    'label' => "Расчетный счет",
                    'attr' => [
                        'data-masktype' => 'bank_wallet',
                        'data-mask' => 1,
                    ]
                ])
        );

        if ($options['partner_type'] == 2 || $options['partner_type'] == 3 || $options['partner_type'] == 4) {
            $builder->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary'),
                'label' => "Сохранить",
            ));
        } else {
            $builder->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary'),
                'label' => "Продолжить",
            ));
        };
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'partner_type' => 0,
        ]);
        $resolver->setAllowedTypes('partner_type', 'int');
    }
}
