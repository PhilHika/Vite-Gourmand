<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminCommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nombrePersonneMin = $options['nombre_personne_min'] ?? 1;

        $builder
            ->add('datePrestation', DateType::class, [
                'label' => 'Date de prestation',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('heureLivraison', TextType::class, [
                'label' => 'Heure de livraison (HH:mm)',
                'attr' => [
                    'class' => 'form-control',
                    'pattern' => '[0-2][0-9]:[0-5][0-9]',
                    'maxlength' => 5,
                ],
            ])
            ->add('nombrePersonne', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'attr' => [
                    'class' => 'form-control',
                    'min' => $nombrePersonneMin,
                ],
            ])
            ->add('pretMateriel', CheckboxType::class, [
                'label' => 'Prêt de matériel',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('restitutionMateriel', CheckboxType::class, [
                'label' => 'Matériel restitué',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('prixMenu', NumberType::class, [
                'label' => 'Prix menu (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('prixLivraison', NumberType::class, [
                'label' => 'Prix livraison (€)',
                'scale' => 2,
                'attr' => ['class' => 'form-control', 'step' => '0.01'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => Commande::STATUT_EN_ATTENTE,
                    'Confirmée' => Commande::STATUT_CONFIRMEE,
                    'En préparation' => Commande::STATUT_EN_PREPARATION,
                    'Livrée' => Commande::STATUT_LIVREE,
                    'Annulée' => Commande::STATUT_ANNULEE,
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'nombre_personne_min' => 1,
        ]);
    }
}
