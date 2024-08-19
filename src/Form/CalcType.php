<?php

namespace App\Form;

use App\Entity\Town;
use App\Entity\Offer;
use App\Repository\TownRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;

class CalcType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditTarget', ChoiceType::class, [
                'label' => 'Цель кредита',
                'attr'  =>  [
                    'class' => 'form-select mb-2',
                ],
                'row_attr'  =>  ['class' => 'mb-2',],
                'placeholder' => 'Выберите цель',
                'required' => true,
                'choices' => Offer::CREDIT_TARGET,
            ])
            ->add('salerType', ChoiceType::class, [
                'label' => 'Продавец недвижимости',
                'row_attr'  =>  ['class' => 'mb-2',],
                'attr'  =>  [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Выберите тип',
                'required' => true,
                'choices' => Offer::SALER_TYPE,
            ])
            ->add('calcPriceType', ChoiceType::class, [
                'label' => 'Расчет по:',
                'row_attr'  =>  ['class' => 'mb-4 mt-3 hidden'],
                'required' => true,
                'choices' => Offer::CALC_PRICE_TYPE,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('time', HiddenType::class, [
                'label' => 'Срок кредита',
                'row_attr'  =>  ['class' => 'mb-2',],
                'required' => true,
                'constraints' => [new NotBlank(null, 'Ошибка. Срок кредита не может быть пустым')],
            ])
            ->add('cost', TextType::class, [
                'label' => 'Стоимость жилья, ₽',
                'row_attr'  =>  ['class' => 'mb-2',],
                'required' => true,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('firstpay', TextType::class, [
                'label' => 'Первоначальный взнос, ₽',
                'row_attr'  =>  ['class' => 'mb-2',],
                'required' => true,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('objectType', ChoiceType::class, [
                'label' => 'Вид недвижимости',
                'row_attr'  =>  ['class' => 'mb-2',],
                'attr'  =>  [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Выберите вид',
                'required' => true,
                'choices' => Offer::OBJECT_TYPE,
            ])
            ->add('isMotherCap', null, [
                'label' => 'Наличие материнского капитала',
                'row_attr' => ['class' => 'mb-2 mb-md-3'],
            ])
            ->add('motherCapSize', TextType::class, [
                'label' => 'Остаток материнского капитала, ₽',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                ],
                'row_attr' => [
                    'class' => 'mt-2 mb-2 mb-md-0 jsMotherCaps',
                ]
            ])
            ->add('stateSupport', ChoiceType::class, [
                'label' => 'Господдержка',
                'attr'  =>  [
                    'class' => 'form-select',
                ],
                'required' => true,
                'choices' => Offer::STATESUPPORT_TYPE,
            ])
            ->add('banks', HiddenType::class, [
                'attr'  =>  [
                    'class' => 'jsBanksInput',
                ],
                'mapped' => false,
            ])
            ->add('withAddAmount', CheckboxType::class, [
                'label' => 'С доп. суммой',
                'attr'  =>  ['class' => 'mb-2 hidden hidden_refinance'],
                'row_attr'  =>  ['class' => 'mb-2 hidden hidden_refinance short',],
                'required' => false,
            ])
            ->add('addAmount', TextType::class, [
                'label' => 'Дополнительная сумма',
                'attr'  =>  ['class' => 'mb-2 hidden hidden_refinance'],
                'row_attr'  =>  ['class' => 'mb-2 hidden hidden_refinance short',],
                'required' => false,
            ])
            ->add('withConsolidation', CheckboxType::class, [
                'label' => 'С консолидацией',
                'attr'  =>  ['class' => 'mb-2 hidden hidden_refinance'],
                'row_attr'  =>  ['class' => 'mb-2 hidden hidden_refinance short',],
                'required' => false,
            ])
            ->add('creditsCount', NumberType::class, [
                'label' => 'Количество кредитов',
                'attr'  =>  ['class' => 'mb-2 hidden hidden_refinance'],
                'row_attr'  =>  ['class' => 'mb-2 hidden hidden_refinance short',],
                'required' => false,
            ])
            ->add('isTarget', ChoiceType::class, [
                'label' => 'Деньги под залог недвижимости',
                'row_attr'  =>  ['class' => 'mb-2 hidden hidden_pledge',],
                'attr'  =>  [
                    'class' => 'form-select hidden hidden_pledge',
                ],
                'required' => false,
                'choices' => Offer::PLEDGE_TARGETS,
                'placeholder' => 'Выберите тип',
            ])
            ->add('isMilitaryMortgage', null, [
                'label' => 'Военная ипотека',
                'row_attr' => ['class' => 'mb-2 mb-md-3'],
            ])
            ->add('ijs', ChoiceType::class, [
                'label' => 'Земельный участок',
                'row_attr'  =>  ['class' => 'mb-0 hidden ijs',],
                'required' => false,
                'choices' => Offer::IJS,
                'placeholder' => 'Выберите тип'
            ])

            ->add(
                $builder->create('about_client', FormType::class, [
                    'inherit_data' => true,
                    'label' => false,
                    'attr'  =>  [
                        'class' => '',
                    ],
                    'label_attr'  =>  [
                        'class' => 'subform__ttl',
                    ],
                    'row_attr'  =>  ['class' => 'mb-2',],
                ])
                    // ->add('age', NumberType::class, [
                    //     'label' => "Возраст старшего заемщика, лет",
                    //     'row_attr'  =>  ['class' => 'mb-2',],
                    // ])
                    ->add('nationality', ChoiceType::class, [
                        'label' => 'Гражданство',
                        'row_attr'  =>  ['class' => 'mb-2',],
                        'required' => true,
                        'choices' => Offer::NATIONALITY_TYPE,
                    ])
                    ->add('hiringType', ChoiceType::class, [
                        'label' => 'Работа',
                        'row_attr'  =>  ['class' => 'mb-2 hiring-buttons',],
                        'attr'  =>  [
                            'class' => 'flexi subform-inlinecheck hidden',
                        ],
                        'expanded' => true,
                        'multiple' => false,
                        'required' => true,
                        'choices' => Offer::HIRING_TYPE,
                    ])
                    ->add('proofMoney', ChoiceType::class, [
                        'label' => 'Подтверждение дохода',
                        'row_attr'  =>  ['class' => 'mb-0',],
                        'required' => true,
                        'choices' => Offer::PROOF_MONEY_TYPE,
                        'placeholder' => 'Выберите подтверждение дохода'
                    ])
                    ->add('town', EntityType::class, [
                        'label' => 'Регион приобретения недвижимости',
                        'class' => Town::class,
                        'query_builder' => function (TownRepository $tr) {
                            return $tr->createQueryBuilder('u')
                                ->andWhere('u.isForCalc = true')
                                ->andWhere('u.isActive = true')
                                ->orderBy('u.title', 'ASC');
                        },
                        'row_attr'  =>  ['class' => 'mb-2',],
                        'placeholder' => 'Выберите регион',
                        'choice_label' => 'title',
                        'required' => true,
                    ])
                    ->add('locality', EntityType::class, [
                        'label' => 'Регион проведения сделки',
                        'class' => Town::class,
                        'query_builder' => function (TownRepository $tr) {
                            return $tr->createQueryBuilder('u')
                                ->andWhere('u.isForCalc = true')
                                ->andWhere('u.isActive = true')
                                ->orderBy('u.title', 'ASC');
                        },
                        'row_attr'  =>  ['class' => 'mb-2',],
                        'placeholder' => 'Выберите регион проведения сделки',
                        'choice_label' => 'title',
                        'required' => true,
                    ])
            )
            ->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary'),
                'label' => "Подобрать варианты",
            ));
        
        $builder->get('about_client')->get('locality')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($entity) {
                return $entity->getTitle();
            }
        ));
        $builder->get('time')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($maskedNums) {
                return intval($maskedNums);
            }
        ));
        $builder->get('cost')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($maskedNums) {
                return intval(str_replace(" ", "", $maskedNums));
            }
        ));
        $builder->get('firstpay')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($maskedNums) {
                return intval(str_replace(" ", "", $maskedNums));
            }
        ));
        $builder->get('addAmount')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($maskedNums) {
                return intval(str_replace(" ", "", $maskedNums));
            }
        ));
        $builder->get('motherCapSize')->addModelTransformer(new CallbackTransformer(
            function ($maskedNums) {
                return $maskedNums;
            },
            function ($maskedNums) {
                return intval(str_replace(" ", "", $maskedNums));
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
            'attr' => [
                'class' => 'js-onceform',
            ],
        ]);
    }

}
