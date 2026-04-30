<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** Formulaire de filtrage des commandes pour l'espace admin (méthode GET, sans CSRF). */
class CommandesFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut',
                'placeholder' => 'Tous les statuts',
                'choices' => [
                    'En attente'                    => Commande::STATUT_EN_ATTENTE,
                    'Confirmée'                     => Commande::STATUT_CONFIRMEE,
                    'En préparation'                => Commande::STATUT_EN_PREPARATION,
                    'Livrée'                        => Commande::STATUT_LIVREE,
                    'Annulée'                       => Commande::STATUT_ANNULEE,
                    'En attente de retour matériel' => Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL,
                    'Terminée'                      => Commande::STATUT_TERMINEE,
                ],
            ])
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'required' => false,
                'label' => 'Client',
                'placeholder' => 'Tous les clients',
                'choice_label' => fn(Utilisateur $u) => sprintf('%s %s (%s)', $u->getPrenom(), $u->getNom(), $u->getEmail()),
                // Limité aux utilisateurs ayant au moins une commande
                'query_builder' => fn($repo) => $repo->createQueryBuilder('u')
                    ->join('u.commandes', 'c')
                    ->groupBy('u.id')
                    ->orderBy('u.nom', 'ASC')
                    ->addOrderBy('u.prenom', 'ASC'),
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
