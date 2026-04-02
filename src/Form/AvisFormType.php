<?php

namespace App\Form;

use App\Entity\Avis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AvisFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note (sur 5)',
                'choices' => [
                    '5 étoiles' => 5,
                    '4 étoiles' => 4,
                    '3 étoiles' => 3,
                    '2 étoiles' => 2,
                    '1 étoile' => 1,
                ],
                // Permet l'affichage en boutons radio plutôt qu'une liste déroulante
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez attribuer une note',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Votre avis',
                'attr' => [
                    'placeholder' => 'Racontez-nous votre expérience sur cette commande et son menu associé...',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir votre avis',
                    ]),
                    new Length(
                        min: 10,
                        max: 2000,
                        minMessage: 'Votre avis doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Votre avis ne peut pas dépasser {{ limit }} caractères',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
        ]);
    }
}
