<?php

namespace App\Form;

use App\Entity\Town;
use App\Entity\User;
use App\Repository\TownRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Ваше имя',
                'required' => true,
                'attr'  =>  [
                    'placeholder' => 'Например, Иван',
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Фамилия',
                'attr'  =>  [
                    'placeholder' => 'Например, Иванов',
                ]
            ])
            ->add('middlename', TextType::class, [
                'label' => 'Отчество',
                'attr'  =>  [
                    'placeholder' => 'Не обязательно',
                ],
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Номер телефона',
                'attr'  =>  [
                    'placeholder' => 'Введите ваш номер телефона',
                ],
                'required' => true,
            ])
            ->add('town', EntityType::class, [
                'label' => 'Регион',
                'class' => Town::class,
                'attr'  =>  [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Выберите город',
                'choice_label' => 'title',
                'required' => true,
                'query_builder' => function (TownRepository $tr) {
                    return $tr->createQueryBuilder('u')
                        ->andWhere('u.isActive = true')
                        ->orderBy('u.title', 'ASC');
                },
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn-primary mt-2 mt-md-3'),
                'label' => "Сохранить профиль",
            ));
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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // 'attr' => [
            //     'class' => 'js-onceform',
            // ],
        ]);
    }
}
