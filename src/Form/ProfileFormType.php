<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'Имя',
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Фамилия',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Телефон',
            ])
            ->add('email', TextType::class, [
                'label' => 'Email',
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Текущий пароль',
                'mapped' => false,
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'Новый пароль',
                'mapped' => false,
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Новый пароль должен быть не короче {{ limit }} символов',
                    ]),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
