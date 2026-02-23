<?php

namespace App\Form;

use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Regime;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => ['class' => 'form-control', 'maxlength' => 50]
            ])
            ->add('description', TextType::class, [ // Using TextType because length is 50 in Entity
                'label' => 'Description courte',
                'attr' => ['class' => 'form-control', 'maxlength' => 50]
            ])
            ->add('prixParPersonne', MoneyType::class, [
                'label' => 'Prix par personne',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control'],
                'html5' => true,
            ])
            ->add('nombrePersonneMinimum', IntegerType::class, [
                'label' => 'Nombre de personnes minimum',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('quantiteRestante', IntegerType::class, [
                'label' => 'Quantité journalière disponible',
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('regime', EntityType::class, [
                'class' => Regime::class,
                'choice_label' => 'libelle',
                'label' => 'Régime alimentaire',
                'required' => false,
                'placeholder' => 'Choisir...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'libelle',
                'label' => 'Thème du menu',
                'attr' => ['class' => 'form-select']
            ])
            ->add('plats', EntityType::class, [
                'class' => Plat::class,
                'choice_label' => 'titrePlat',
                'label' => 'Plats inclus',
                'multiple' => true,
                'expanded' => false, // Set to true for checkboxes if preferred
                'attr' => ['class' => 'form-select', 'size' => 10], // size to show multiple items
                'by_reference' => false, // Important for ManyToMany to call add/remove methods
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
