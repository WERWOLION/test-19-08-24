<?php

namespace App\Form;

use App\Entity\Offer;
use App\Form\BuyerFormType;
use App\Form\BuyerWorkType;
use Doctrine\DBAL\Types\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class On2DocFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('buyer', BuyerWorkType::class, [
            'label' => false,
            'required' => false,
            'attr' => [
                'class' => 'row',
            ],
        ])
        ->add('cobuyers', CollectionType::class, [
            'attr' => [
                'class' => 'jsCanRemove',
            ],
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => BuyerWorkType::class,
            'by_reference' => false,
            'mapped' => true,
            'entry_options' => [
                'can_hide' => true,
                'attr' => [
                    'class' => 'row pb-2 pt-3 border-bottom',
                ],
            ],
        ]);
        $builder->add('save', SubmitType::class, array(
            'attr' => array('class' => 'btn-primary'),
            'label' => "Отправить информацию о доходах",
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offer::class,
        ]);
    }
}
