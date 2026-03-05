<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => 'Minimum 10 caractères'],
                    'constraints' => [
                        new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
                        new Length([
                            'min' => 10,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                            'max' => 4096,
                        ]),
                        new Regex([
                            'pattern' => '/[A-Z]/',
                            'message' => 'Le mot de passe doit contenir au moins une majuscule',
                        ]),
                        new Regex([
                            'pattern' => '/[a-z]/',
                            'message' => 'Le mot de passe doit contenir au moins une minuscule',
                        ]),
                        new Regex([
                            'pattern' => '/[0-9]/',
                            'message' => 'Le mot de passe doit contenir au moins un chiffre',
                        ]),
                        new Regex([
                            'pattern' => '/[^A-Za-z0-9]/',
                            'message' => 'Le mot de passe doit contenir au moins un caractère spécial',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['placeholder' => 'Confirmez votre nouveau mot de passe'],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'reset_password', // éviter la collision avec le CSRF authenticate du form_login
        ]);
    }
}
