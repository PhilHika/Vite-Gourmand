<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminCommandeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $nombrePersonneMin = $options['nombre_personne_min'] ?? 1;
        $pretMateriel = $options['pret_materiel'] ?? false;
        $restitutionMateriel = $options['restitution_materiel'] ?? false;
        $statutActuel = $options['statut_actuel'] ?? Commande::STATUT_EN_ATTENTE;

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
            ->add('adresseLivraison', TextareaType::class, [
                'label' => 'Adresse de livraison',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('villeLivraison', TextType::class, [
                'label' => 'Ville de livraison',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('paysLivraison', TextType::class, [
                'label' => 'Pays de livraison',
                'required' => false,
                'attr' => ['class' => 'form-control'],
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
                    $this->getLabelAttenteRetour($pretMateriel) => Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL,
                    $this->getLabelTerminee($statutActuel, $pretMateriel, $restitutionMateriel) => Commande::STATUT_TERMINEE,
                ],
                'choice_attr' => $this->buildStatutChoiceAttr($statutActuel, $pretMateriel, $restitutionMateriel),
                'attr' => ['class' => 'form-select', 'id' => 'commande-statut-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'nombre_personne_min' => 1,
            'pret_materiel' => false,
            'restitution_materiel' => false,
            'statut_actuel' => Commande::STATUT_EN_ATTENTE,
        ]);
    }

    private function buildStatutChoiceAttr(string $statutActuel, bool $pretMateriel, bool $restitutionMateriel): \Closure
    {
        return function (string $value) use ($statutActuel, $pretMateriel, $restitutionMateriel): array {
            // Le statut actuel ne doit jamais être désactivé :
            // un <option disabled selected> n'est pas soumis par le navigateur → setStatut(null) → TypeError
            if ($value === $statutActuel) {
                return [];
            }

            // "En attente de retour matériel" : uniquement depuis "livrée" avec prêt et sans restitution
            if ($value === Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL) {
                $peutAttenteRetour = $statutActuel === Commande::STATUT_LIVREE
                    && $pretMateriel
                    && !$restitutionMateriel;

                return $peutAttenteRetour ? [] : ['disabled' => 'disabled'];
            }

            // "Terminée" : depuis "livrée" sans prêt, depuis "livrée" avec prêt+restitution,
            //              ou depuis "en attente retour" avec restitution
            if ($value === Commande::STATUT_TERMINEE) {
                $peutTerminer = $statutActuel === Commande::STATUT_LIVREE && !$pretMateriel
                    || $statutActuel === Commande::STATUT_LIVREE && $pretMateriel && $restitutionMateriel
                    || $statutActuel === Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL && $restitutionMateriel;

                return $peutTerminer ? [] : ['disabled' => 'disabled'];
            }

            return [];
        };
    }

    private function getLabelAttenteRetour(bool $pretMateriel): string
    {
        if (!$pretMateriel) {
            return 'En attente de retour matériel (prêt : non)';
        }

        return 'En attente de retour matériel';
    }

    private function getLabelTerminee(string $statutActuel, bool $pretMateriel, bool $restitutionMateriel): string
    {
        // Statut non éligible
        if ($statutActuel !== Commande::STATUT_LIVREE && $statutActuel !== Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL) {
            return 'Terminée (requiert : livrée)';
        }

        // Livrée ou en attente retour, prêt matériel mais restitution non cochée
        if ($pretMateriel && !$restitutionMateriel) {
            return 'Terminée (restitution : non)';
        }

        return 'Terminée';
    }
}
