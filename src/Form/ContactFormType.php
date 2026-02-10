<?php

namespace App\Form;

use App\DTO\ContactData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre nom & prenom',
                'attr' => [
                    'placeholder' => 'Entrez votre nom',
                    'class' => 'form-control',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre email',
                'attr' => [
                    'placeholder' => 'Entrez votre adresse email',
                    'class' => 'form-control',
                ],
            ])
            ->add('code_postal', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'placeholder' => '75001',
                    'class' => 'form-control',
                    'maxlength' => 5,
                ],
            ])
            ->add('sujet', TextType::class, [
                'label' => 'Sujet',
                'attr' => [
                    'placeholder' => 'Sujet de votre message',
                    'class' => 'form-control',
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'placeholder' => 'Écrivez votre message ici...',
                    'class' => 'form-control',
                    'rows' => 6,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactData::class,
        ]);
    }
}
