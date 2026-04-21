<?php

namespace App\Service;

use App\Entity\Commande;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

class CommandeMailerService
{
    private const string EXPEDITEUR = 'noreply@vite-et-gourmand.fr';

    private const string TPL_STATUT        = 'emails/commande_statut.html.twig';
    private const string TPL_TERMINEE      = 'emails/commande_terminee.html.twig';
    private const string TPL_CONFIRMATION  = 'emails/commande_confirmation.html.twig';
    private const string TPL_ATTENTE_RETOUR = 'emails/commande_attente_retour_materiel.html.twig';

    private const array STATUT_LABELS = [
        Commande::STATUT_EN_ATTENTE                  => 'En attente',
        Commande::STATUT_CONFIRMEE                   => 'Confirmée',
        Commande::STATUT_EN_PREPARATION              => 'En préparation',
        Commande::STATUT_LIVREE                      => 'Livrée',
        Commande::STATUT_ANNULEE                     => 'Annulée',
        Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL  => 'En attente de retour matériel',
        Commande::STATUT_TERMINEE                    => 'Terminée',
    ];

    public function __construct(
        private MailerInterface $mailer,
        private UtilisateurRepository $utilisateurRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    /**
     * Envoie un email de confirmation après la création d'une commande.
     */
    public function envoyerConfirmation(Commande $commande): void
    {
        $user = $commande->getUtilisateur();
        $sujet = $this->sujetCommande($commande, 'Confirmation de votre commande');

        $this->mailer->send(
            $this->ajouterLogo(
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->to($user->getEmail())
                    ->subject($sujet)
                    ->htmlTemplate(self::TPL_CONFIRMATION)
                    ->context(['commande' => $commande, 'isStaff' => false])
            )
        );

        $this->notifierGestionnaires(
            $this->sujetCommande($commande, 'Nouvelle commande'),
            self::TPL_CONFIRMATION,
            ['commande' => $commande, 'isStaff' => true]
        );
    }

    /**
     * Envoie un email au client lorsque le statut de sa commande change.
     */
    public function envoyerChangementStatut(Commande $commande, string $ancienStatut): void
    {
        if ($ancienStatut === $commande->getStatut()) {
            return;
        }

        $user = $commande->getUtilisateur();
        $labelStatut = self::STATUT_LABELS[$commande->getStatut()] ?? $commande->getStatut();

        if ($commande->getStatut() === Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL) {
            $sujet = $this->sujetCommande($commande, 'Retour matériel en attente');
            $contexteClient = ['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => false];
            $contexteStaff  = ['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => true];

            $this->mailer->send(
                $this->ajouterLogo(
                    new TemplatedEmail()
                        ->from(self::EXPEDITEUR)
                        ->to($user->getEmail())
                        ->subject($sujet)
                        ->htmlTemplate(self::TPL_ATTENTE_RETOUR)
                        ->context($contexteClient)
                )
            );
            $this->notifierGestionnaires($sujet, self::TPL_ATTENTE_RETOUR, $contexteStaff);

            return;
        }

        // Statut "Terminée" : email dédié au client pour inciter aux avis
        if ($commande->getStatut() === Commande::STATUT_TERMINEE) {
            $sujet = $this->sujetCommande($commande, 'Terminée');

            $this->mailer->send(
                $this->ajouterLogo(
                    new TemplatedEmail()
                        ->from(self::EXPEDITEUR)
                        ->to($user->getEmail())
                        ->subject($sujet)
                        ->htmlTemplate(self::TPL_TERMINEE)
                        ->context(['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => false])
                )
            );
            $this->notifierGestionnaires(
                $this->sujetCommande($commande, "Statut : $labelStatut"),
                self::TPL_STATUT,
                ['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => true]
            );

            return;
        }

        $contexte = ['commande' => $commande, 'labelStatut' => $labelStatut];
        $sujet = $this->sujetCommande($commande, 'Statut mis à jour');

        $this->mailer->send(
            $this->ajouterLogo(
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->to($user->getEmail())
                    ->subject($sujet)
                    ->htmlTemplate(self::TPL_STATUT)
                    ->context($contexte + ['isStaff' => false])
            )
        );

        $this->notifierGestionnaires(
            $this->sujetCommande($commande, "Statut : $labelStatut"),
            self::TPL_STATUT,
            $contexte + ['isStaff' => true]
        );
    }

    private function sujetCommande(Commande $commande, string $suffixe): string
    {
        $numero = $commande->getNumeroCommande();

        return "Commande $numero — $suffixe";
    }

    /**
     * Ajoute le logo aux emails.
     */
    private function ajouterLogo(TemplatedEmail $email): TemplatedEmail
    {
        return $email->embedFromPath(
            "{$this->projectDir}/public/images/VetG-logo.jpg",
            'logo'
        );
    }

    /**
     * Retourne le label lisible d'un statut.
     */
    public static function getStatutLabel(string $statut): string
    {
        return self::STATUT_LABELS[$statut] ?? $statut;
    }

    /**
     * Envoie un email à tous les gestionnaires (ROLE_SALARIE + ROLE_ADMIN).
     */
    private function notifierGestionnaires(string $sujet, string $template, array $context): void
    {
        foreach ($this->utilisateurRepository->findGestionnaires() as $gestionnaire) {
            $this->mailer->send(
                $this->ajouterLogo(
                    new TemplatedEmail()
                        ->from(self::EXPEDITEUR)
                        ->to($gestionnaire->getEmail())
                        ->subject("[STAFF] $sujet")
                        ->htmlTemplate($template)
                        ->context($context)
                )
            );
        }
    }
}
