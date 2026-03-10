<?php

namespace App\Form;

use App\Entity\Regime;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenusFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prixMin', NumberType::class, [
                'required' => false,
                'label' => 'Prix min (€/pers)',
                'attr' => ['placeholder' => 'Min', 'min' => 0, 'step' => '0.50'],
                'html5' => true,
            ])
            ->add('prixMax', NumberType::class, [
                'required' => false,
                'label' => 'Prix max (€/pers)',
                'attr' => ['placeholder' => 'Max', 'min' => 0, 'step' => '0.50'],
                'html5' => true,
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'libelle',
                'required' => false,
                'label' => 'Thème',
                'placeholder' => 'Tous les thèmes',
            ])
            ->add('regime', EntityType::class, [
                'class' => Regime::class,
                'choice_label' => 'libelle',
                'required' => false,
                'label' => 'Régime',
                'placeholder' => 'Tous les régimes',
            ])
            ->add('nombrePersonne', IntegerType::class, [
                'required' => false,
                'label' => 'Minimum de personnes',
                'attr' => ['placeholder' => 'Nb personnes', 'min' => 1],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
