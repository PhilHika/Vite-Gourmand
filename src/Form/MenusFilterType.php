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

/**
 * Filtres de la liste des menus (méthode GET, sans protection CSRF).
 *
 * Utilisé par MenuApiController comme parseur/validateur des query params GET
 * envoyés par la SPA Vue (préfixe `menus_filter[clé]=valeur`). Ce formulaire
 * n'est plus rendu en HTML : Vue construit son propre formulaire dans
 * vue-app/src/components/MenuFilters.vue. Les options de rendu (label, attr,
 * placeholder, html5, choice_label) sont donc volontairement omises.
 */
class MenusFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prixMin', NumberType::class, [
                'required' => false,
            ])
            ->add('prixMax', NumberType::class, [
                'required' => false,
            ])
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'required' => false,
            ])
            ->add('regime', EntityType::class, [
                'class' => Regime::class,
                'required' => false,
            ])
            ->add('nombrePersonne', IntegerType::class, [
                'required' => false,
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

    /**
     * Préfixe vidé : les champs apparaissent à plat dans la query string
     * (?prixMin=10&theme=2) au lieu de ?menus_filter[prixMin]=10&menus_filter[theme]=2.
     * URLs plus lisibles et partageables. Pas de risque de collision tant que la
     * page /menu n'a qu'un seul form (pas de pagination ni autres params concurrents).
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
