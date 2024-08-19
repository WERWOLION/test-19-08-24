<?php

namespace App\Form;

use App\Entity\Town;
use App\Entity\User;
use App\Entity\PreUser;
use App\Repository\TownRepository;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Ваш email:',
                'mapped' => false,
                'data' => $options['email'],
                'attr'  =>  [
                    'readonly' => true,
                ],
                'row_attr' => [
                    'style' => "display: none;",
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            ->add('phoneTemp', null, [
                'label' => 'Ваш номер телефона:',
                'mapped' => false,
                'data' => $options['phone'],
                'attr'  =>  [
                    'readonly' => true,
                ],
                'row_attr' => [
                    'style' => "display: none;",
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            // ->add('purposeReg', ChoiceType::class, [
            //     'label' => 'Цель регистрации',
            //     'attr'  =>  [
            //         'class' => 'form-select',
            //     ],
            //     'placeholder' => 'Выберите цель регистрации',
            //     'choice_label' => 'title',
            //     'required' => true,
            //     'row_attr' => [
            //         'class' => 'col-12 col-lg-6 mb-3'
            //     ],
            //     'choices' => [
            //         "Получить ипотеку" => 1,
            //         "Стать партнером" => 2,
            //     ]
            // ])
            ->add('firstname', null, [
                'label' => 'Имя',
                'required' => true,
                'attr'  =>  [
                    'placeholder' => 'Введите ваше имя',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            ->add('firstname', null, [
                'label' => 'Имя',
                'required' => true,
                'attr'  =>  [
                    'placeholder' => 'Введите ваше имя',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            ->add('lastname', null, [
                'label' => 'Фамилия',
                'required' => true,
                'attr'  =>  [
                    'placeholder' => 'Введите фамилию',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            ->add('middlename', null, [
                'label' => 'Отчество',
                'attr'  =>  [
                    'placeholder' => 'Не обязательно',
                ],
                'row_attr' => [
                    'class' => 'col-12 col-lg-6 mb-3'
                ]
            ])
            ->add('town', EntityType::class, [
                'label' => 'Регион',
                'class' => Town::class,
                'attr'  =>  [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Выберите регион',
                'choice_label' => 'title',
                'required' => true,
                'row_attr' => [
                    'class' => 'col-12 col-lg-6 mb-3'
                ],
                'query_builder' => function (TownRepository $tr) {
                    return $tr->createQueryBuilder('u')
                        ->andWhere('u.isActive = true')
                        ->orderBy('u.title', 'ASC');
                },
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'constraints' => [
                    new NotBlank(),
                ],
                'mapped' => false,
                'first_options' => [
                    'always_empty' => false,
                    'label' => 'Пароль',
                    'attr' => ['autocomplete' => 'new-password'],
                    'row_attr' => [
                        'class' => 'col-12 col-lg-6 mb-3'
                    ]
                ],
                'second_options' => [
                    'always_empty' => false,
                    'label' => 'Повтор пароля',
                    'attr' => ['autocomplete' => 'new-password'],
                    'row_attr' => [
                        'class' => 'col-12 col-lg-6 mb-3'
                    ]
                ],
                'invalid_message' => 'Поля паролей должны совпадать.',
            ])
            ->add('agreeOferta', CheckboxType::class, [
                'data' => true,
                'label' => 'Согласен с <a href="/page/oferta" class="bluelink" target="_blank">правилами использования платформы</a>',
                'label_html' => true,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Вы должны согласиться с офертой',
                    ]),
                ],
                'label_attr' => [
                    'class' => 'loginform__access'
                ],
                'row_attr' => [
                    'class' => 'col-12 mb-4'
                ]
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary btn-lg'),
                'label' => "Зарегистрироваться",
                'row_attr' => [
                    'class' => 'col-12 col-lg-6'
                ]
            ));;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'email' => '',
            'phone' => '',
            'data_class' => User::class,
            'attr' => [
                'class' => 'row js-onceform',
            ],
        ]);
        $resolver->setAllowedTypes('email', 'string');
        $resolver->setAllowedTypes('phone', 'string');
    }
}
