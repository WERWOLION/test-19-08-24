<?php

namespace App\Form;

use App\Entity\Buyer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class BuyerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastname', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-3 mb-2',
                ],
                'label' => "Фамилия",
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('firstname', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-3 mb-2',
                ],
                'label' => "Имя",
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('middlename', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-3 mb-2',
                ],
                'label' => "Отчество",
                'attr' => [
                    'placeholder' => "Не обязательно",
                ],
            ])
            ->add('phone', TelType::class, [
                'row_attr' => [
                    'class' => 'col-12 col-md-3 mb-2',
                ],
                'label' => "Номер телефона",
                'attr' => [
                    'placeholder' => "Введите номер телефона",
                    'data-mask' => '1',
                    'data-masktype' => 'phone'
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '*_*',
                        'match' => false,
                        'message' => 'Номер телефона заполнен с ошибкой',
                    ]),
                ],
                'required' => true,
            ])
            ->add('birthDate', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-3 mb-2',
                ],
                'required' => true,
                'label' => "Дата рождения",
                'attr' => [
                    'placeholder' => "дд.мм.гггг",
                    'data-mask' => '1',
                    'data-masktype' => 'date'
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '*_*',
                        'match' => false,
                        'message' => 'Дата рождения заполнена с ошибкой',
                    ]),
                ],
            ]);


            $builder->add('pasportSeries', null, [
                'row_attr' => [
                    'class' => 'col-5 col-md-2 mb-2',
                ],
                'required' => true,
                'label' => "Серия паспорта",
                'attr' => [
                    'placeholder' => "Без пробелов",
                    'data-mask' => '1',
                    'data-masktype' => $options['passport_mask'] ? 'pasport_series' : null,
                ],
                'constraints' => [],
            ])
            ->add('pasportNum', null, [
                'row_attr' => [
                    'class' => 'col-7 col-md-4 mb-2',
                ],
                'required' => true,
                'label' => "Номер паспорта",
                'attr' => [
                    'placeholder' => "Без пробелов",
                    'data-mask' => '1',
                    'data-masktype' => $options['passport_mask'] ? 'pasport_number' : null,
                ],
                'constraints' => [],
            ]);


            $builder->add('pasportDate', null, [
                'row_attr' => [
                    'class' => 'col-6 col-md-3 mb-2',
                ],
                'required' => true,
                'label' => "Дата выдачи паспорта",
                'attr' => [
                    'placeholder' => "дд.мм.гггг",
                    'data-mask' => '1',
                    'data-masktype' => 'date'
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '*_*',
                        'match' => false,
                        'message' => 'Дата выдачи заполнена с ошибкой',
                    ]),
                ],
            ])
            ->add('pasportCode', null, [
                'row_attr' => [
                    'class' => 'col-6 col-md-3 mb-2',
                ],
                'required' => true,
                'label' => "Код подразделения",
                'attr' => [
                    'placeholder' => "Введите код подразделения",
                    'class' => 'jsPasportCode',
                ],
            ])
            ->add('pasportDescript', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-9 mb-2',
                ],
                'required' => true,
                'label' => "Кем выдан",
                'attr' => [
                    'placeholder' => "Заполняется автоматически",
                    'class' => 'jsPasportDescipt',
                ],
            ])
            ->add('address', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-6 mb-2',
                ],
                'attr' => [
                    'placeholder' => "Начините вводить...",
                    'data-adress' => "1",
                ],
                'required' => true,
                'label' => "Место рождения",
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('passportAddress', null, [
                'row_attr' => [
                    'class' => 'col-12 col-md-6 mb-2',
                ],
                'attr' => [
                    'placeholder' => "Начините вводить...",
                    'data-adress' => "1",
                ],
                'required' => true,
                'label' => "Адрес регистрации",
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('autofill', ButtonType::class, [
                'row_attr' => [
                    'class' => 'col-auto pt-2',
                ],
                'label' => 'Выбрать из сохраненных',
                'attr' => [
                    'class' => "btn btn-secondary btn-sm",
                    'data-hystmodal' => "#jsBuyersModal",
                    'data-contactid' => 'buyer',
                ],
            ])
            ->add('isSave', CheckboxType::class, [
                'row_attr' => [
                    'class' => 'col-auto pt-2 mt-1',
                ],
                'required' => false,
                'label' => 'Сохранить контакт',
                'mapped' => false,
                'data' => true,
            ])
            // ->add('accessPermission', CheckboxType::class, [
            //     'row_attr' => [
            //         'class' => 'col-auto pt-2 mt-1',
            //     ],
            //     'required' => true,
            //     'label' => 'Согласие с обработкой ПНД',
            // ]);
        ;

        /**
         * Преобразуем введенный номер телефона в стандартный формат
         */
        $builder->get('phone')->addModelTransformer(new CallbackTransformer(
            function ($dateModelToView) {
                return $dateModelToView;
            },
            function ($dateViewToModel) {
                return str_replace(["-", " ", "(", ")"], "", $dateViewToModel);
            }
        ));

        if($options['remove_button']){
            $builder->add('remove', ButtonType::class, [
                'row_attr' => [
                    'class' => 'sobuyers__delete',
                ],
                'label' => '&times;',
                'label_html' => true,
                'attr' => [
                    'class' => "jsMinus btn-secondary",
                ],
            ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Buyer::class,
            'remove_button' => false,
            'passport_mask' => true, //Показывать ли маску ввода на паспорт
        ]);
        $resolver->setAllowedTypes('remove_button', 'bool');
        $resolver->setAllowedTypes('passport_mask', 'bool');
    }
}
