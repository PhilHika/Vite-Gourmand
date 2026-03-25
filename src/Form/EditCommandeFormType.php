<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditCommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nombrePersonneMin = $options['nombre_personne_min'] ?? 1;

        $builder
            ->add('datePrestation', DateType::class, [
                'label' => 'Date de prestation',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'min' => (new \DateTime('+1 day'))->format('Y-m-d')],
            ])
            ->add('heureLivraison', TextType::class, [
                'label' => 'Heure de livraison (HH:mm)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '12:00',
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
            ->add('adresseLivraison', TextareaType::class, [
                'label' => 'Adresse de livraison',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('villeLivraison', TextType::class, [
                'label' => 'Ville de livraison',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paysLivraison', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pretMateriel', CheckboxType::class, [
                'label' => 'Prêt de matériel (tables, chaises, etc.)',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'nombre_personne_min' => 1,
        ]);
    }
}
