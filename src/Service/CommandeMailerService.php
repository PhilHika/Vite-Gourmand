<?php

namespace App\Service;

use App\Entity\Commande;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class CommandeMailerService
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';

    private const STATUT_LABELS = [
        Commande::STATUT_EN_ATTENTE     => 'En attente',
        Commande::STATUT_CONFIRMEE      => 'Confirmée',
        Commande::STATUT_EN_PREPARATION => 'En préparation',
        Commande::STATUT_LIVREE         => 'Livrée',
        Commande::STATUT_ANNULEE        => 'Annulée',
    ];

    public function __construct(
        private MailerInterface $mailer,
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    /**
     * Envoie un email de confirmation après la création d'une commande.
     */
    public function envoyerConfirmation(Commande $commande): void
    {
        $user = $commande->getUtilisateur();

        $this->mailer->send(
            new TemplatedEmail()
                ->from(self::EXPEDITEUR)
                ->to($user->getEmail())
                ->subject('Confirmation de votre commande ' . $commande->getNumeroCommande())
                ->htmlTemplate('emails/commande_confirmation.html.twig')
                ->context(['commande' => $commande, 'isStaff' => false])
        );

        $this->notifierGestionnaires(
            'Nouvelle commande ' . $commande->getNumeroCommande(),
            'emails/commande_confirmation.html.twig',
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

        // Commande.status === "Livrée"
        // Envoi email au client pour inciter aux retours/avis
        if ($commande->getStatut() === Commande::STATUT_LIVREE) {
            $this->mailer->send(
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->to($user->getEmail())
                    ->subject('Commande ' . $commande->getNumeroCommande() . ' — Livrée')
                    ->htmlTemplate('emails/commande_livree.html.twig')
                    ->context(['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => false])
            );
            // notify gestionnaire avec l'email classique de changement de status
            $this->notifierGestionnaires(
                'Commande ' . $commande->getNumeroCommande() . ' — Statut : ' . $labelStatut,
                'emails/commande_statut.html.twig',
                ['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => true]
            );

            return;
        }

        $this->mailer->send(
            new TemplatedEmail()
                ->from(self::EXPEDITEUR)
                ->to($user->getEmail())
                ->subject('Commande ' . $commande->getNumeroCommande() . ' — Statut mis à jour')
                ->htmlTemplate('emails/commande_statut.html.twig')
                ->context(['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => false])
        );

        $this->notifierGestionnaires(
            'Commande ' . $commande->getNumeroCommande() . ' — Statut : ' . $labelStatut,
            'emails/commande_statut.html.twig',
            ['commande' => $commande, 'labelStatut' => $labelStatut, 'isStaff' => true]
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
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->to($gestionnaire->getEmail())
                    ->subject('[STAFF] ' . $sujet)
                    ->htmlTemplate($template)
                    ->context($context)
            );
        }
    }
}
