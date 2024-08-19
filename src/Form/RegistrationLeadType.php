<?php

namespace App\Form;

use App\Entity\PreUser;
use App\Entity\Town;
use App\Entity\User;
use App\Repository\TownRepository;
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
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegistrationLeadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Укажите Email',
                'attr'  =>  [
                    'placeholder' => 'Ваш E-mail',
                ],
                'row_attr' => [
                    'class' => 'form-floating mb-3',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Ваш номер телефона',
                'attr'  =>  [
                    'placeholder' => 'Введите ваш номер телефона',
                    'data-mask' => '1',
                    'data-masktype' => 'phone'
                ],
                'row_attr' => [
                    'class' => 'form-floating mb-3',
                ],
                'constraints' => [
                    new Regex([
                        'pattern' => '*_*',
                        'match' => false,
                        'message' => 'Номер телефона заполнен с ошибкой',
                    ]),
                ],
            ])
            ->add('agreeOferta', CheckboxType::class, [
                'data' => true,
                'label' => 'Согласен на <a href="https://lk.ipoteka.life/page/newsletter" class="bluelink" target="_blank">получение информационной рассылки</a>',
                'label_html' => true,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Вы должны согласиться на получение информационной рассылки',
                    ]),
                ],
                'label_attr' => [
                    'class' => 'loginform__access'
                ],
            ])
            ->add('agreeOferta2', CheckboxType::class, [
                'data' => true,
                'label' => 'Согласен на <a href="https://lk.ipoteka.life/page/personal-data" class="bluelink" target="_blank">обработку персональных данных</a>',
                'label_html' => true,
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Вы должны согласиться на обработку персональных данных',
                    ]),
                ],
                'label_attr' => [
                    'class' => 'loginform__access'
                ],
            ])
            ->add('token', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('save', SubmitType::class, array(
                'attr' => [
                    'class' => 'btn-primary btn-lg',
                ],
                'label' => "Получить доступ",
            ));;
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
            'data_class' => PreUser::class,
            'attr' => [
                'class' => 'js-onceform js-recapcha',
            ],
        ]);
    }
}
