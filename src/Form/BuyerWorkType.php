<?php

namespace App\Form;

use App\Entity\Buyer;
use App\Entity\Offer;
use App\Form\BuyerFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Length;

class BuyerWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('fio', TextType::class, [
            'label' => false,
            'required' => false,
            'disabled' => true,
            'property_path' => 'other[fio]',
            'row_attr' => [
                'class' => 'col-12 pb-2',
            ],
        ]);

        if($options['can_hide']){
            $builder->add('isNotMoney', CheckboxType::class, [
                'row_attr' => [
                    'class' => 'col-12 pb-2',
                ],
                'attr' => [
                    'class' => 'js-notmoney',
                ],
                'required' => false,
                'label' => 'Без дохода',
                'property_path' => 'other[isNotMoney]',
            ]);
        }


        $builder->add('inn', TextType::class, [
            'label' => 'ИНН организации работодателя',
            'required' => false,
            'property_path' => 'other[inn]',
            'row_attr' => [
                'class' => 'col-12 col-md-3 pb-2 js-formitem',
            ],
            'constraints' => [
                new Length([
                    'min' => 10,
                    'max' => 12,
                    'minMessage' => 'В ИНН должно быть от 10 до 12 цифр',
                    'maxMessage' => 'В ИНН должно быть 10 до 12 цифр',
                ]),
            ],
            'attr' => [
                'data-mask' => '1',
                'data-masktype' => 'inn',
            ],
        ])
        ->add('work_phone', TextType::class, [
            'label' => 'Телефон рабочий',
            'required' => false,
            'property_path' => 'other[work_phone]',
            'row_attr' => [
                'class' => 'col-12 col-md-3 pb-2 js-formitem',
            ],
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
        ])
        ->add('work_address', TextType::class, [
            'label' => 'Фактический адрес работодателя',
            'required' => false,
            'property_path' => 'other[work_address]',
            'row_attr' => [
                'class' => 'col-12 col-md-6 pb-2 js-formitem',
            ],
        ])
        ->add('proff', TextType::class, [
            'label' => 'Занимаемая должность',
            'required' => false,
            'property_path' => 'other[proff]',
            'row_attr' => [
                'class' => 'col-12 col-md-3 pb-2 js-formitem',
            ],
        ])
        ->add('work_money', NumberType::class, [
            'label' => 'Средний ежемесячный доход, ₽',
            'html5' => true,
            'required' => false,
            'property_path' => 'other[work_money]',
            'row_attr' => [
                'class' => 'col-12 col-md-3 pb-2 js-formitem',
            ],
        ])
        ->add('work_year', NumberType::class, [
            'label' => 'Стаж на текущем месте: лет',
            'required' => false,
            'html5' => true,
            'property_path' => 'other[work_year]',
            'row_attr' => [
                'class' => 'col-7 col-md-3 pb-2 js-formitem',
            ],
            'attr' => [
                'min' => 0,
                'max' => 100,
                'placeholder' => 'Введите 0 если меньше года',
            ],
        ])
        ->add('work_month', NumberType::class, [
            'label' => 'мес.',
            'required' => false,
            'html5' => true,
            'property_path' => 'other[work_month]',
            'row_attr' => [
                'class' => 'col-5 col-md-3 pb-2 js-formitem',
            ],
            'attr' => [
                'min' => 0,
                'max' => 11,
                'placeholder' => 'от 0 до 11',
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Buyer::class,
            'label' => false,
            'can_hide' => false, //Можно отметить галочку "Без дохода и скрыть"
        ]);
        $resolver->setAllowedTypes('can_hide', 'bool');
    }
}
