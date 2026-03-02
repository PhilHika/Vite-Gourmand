<?php

namespace App\Service;

use App\Entity\Commande;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CommandeMailerService
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';
    private const DATE_FORMAT = 'd/m/Y';

    private const STATUT_LABELS = [
        Commande::STATUT_EN_ATTENTE => 'En attente',
        Commande::STATUT_CONFIRMEE => 'Confirmée',
        Commande::STATUT_EN_PREPARATION => 'En préparation',
        Commande::STATUT_LIVREE => 'Livrée',
        Commande::STATUT_ANNULEE => 'Annulée',
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

        // Email client
        $email = (new Email())
            ->from(self::EXPEDITEUR)
            ->to($user->getEmail())
            ->subject('Confirmation de votre commande ' . $commande->getNumeroCommande())
            ->html($this->buildConfirmationHtml($commande, false));

        $this->mailer->send($email);

        // Notifier les gestionnaires
        $this->notifierGestionnaires(
            'Nouvelle commande ' . $commande->getNumeroCommande(),
            $this->buildConfirmationHtml($commande, true)
        );
    }

    /**
     * Envoie un email au client lorsque le statut de sa commande change.
     */
    public function envoyerChangementStatut(Commande $commande, string $ancienStatut): void
    {
        $user = $commande->getUtilisateur();
        $nouveauStatut = $commande->getStatut();

        // Ne rien envoyer si le statut n'a pas changé
        if ($ancienStatut === $nouveauStatut) {
            return;
        }

        $labelStatut = self::STATUT_LABELS[$nouveauStatut] ?? $nouveauStatut;

        // Email client
        $email = (new Email())
            ->from(self::EXPEDITEUR)
            ->to($user->getEmail())
            ->subject('Commande ' . $commande->getNumeroCommande() . ' — Statut mis à jour')
            ->html($this->buildStatutHtml($commande, $labelStatut, false));

        $this->mailer->send($email);

        // Notifier les gestionnaires
        $this->notifierGestionnaires(
            'Commande ' . $commande->getNumeroCommande() . ' — Statut : ' . $labelStatut,
            $this->buildStatutHtml($commande, $labelStatut, true)
        );
    }

    /**
     * Retourne le label lisible d'un statut.
     */
    public static function getStatutLabel(string $statut): string
    {
        return self::STATUT_LABELS[$statut] ?? $statut;
    }

    // ========== Méthodes privées de construction HTML ==========

    /**
     * Construit le HTML pour un email de confirmation de commande.
     */
    private function buildConfirmationHtml(Commande $commande, bool $isStaff): string
    {
        $user = $commande->getUtilisateur();
        $menu = $commande->getMenu();

        $titre = $isStaff ? 'Nouvelle commande reçue' : 'Merci pour votre commande !';
        $intro = $isStaff
            ? sprintf(
                '<p><strong>Client :</strong> %s (%s)</p><p><strong>N° :</strong> %s</p>',
                htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()),
                htmlspecialchars($user->getEmail()),
                htmlspecialchars($commande->getNumeroCommande())
            )
            : sprintf(
                '<p>Bonjour <strong>%s</strong>,</p><p>Votre commande <strong>%s</strong> a bien été enregistrée.</p>',
                htmlspecialchars($user->getPrenom()),
                htmlspecialchars($commande->getNumeroCommande())
            );

        $details = sprintf(
            '<ul>
                <li><strong>Menu :</strong> %s</li>
                <li><strong>Date de prestation :</strong> %s</li>
                <li><strong>Heure de livraison :</strong> %s</li>
                <li><strong>Nombre de personnes :</strong> %d</li>
                <li><strong>Prix menu :</strong> %s €</li>
            </ul>',
            htmlspecialchars($menu->getTitre()),
            $commande->getDatePrestation()->format(self::DATE_FORMAT),
            htmlspecialchars($commande->getHeureLivraison()),
            $commande->getNombrePersonne(),
            number_format($commande->getPrixMenu(), 2, ',', ' ')
        );

        $footer = $isStaff ? '' : '<p>Notre équipe vous contactera pour confirmer les détails de livraison.</p>';

        return sprintf('<h2>%s</h2>%s%s%s%s', $titre, $intro, $details, $footer, $this->getSignature());
    }

    /**
     * Construit le HTML pour un email de changement de statut.
     */
    private function buildStatutHtml(Commande $commande, string $labelStatut, bool $isStaff): string
    {
        $user = $commande->getUtilisateur();
        $menu = $commande->getMenu();

        $titre = $isStaff ? 'Changement de statut' : 'Mise à jour de votre commande';
        $intro = $isStaff
            ? sprintf(
                '<p><strong>Client :</strong> %s (%s)</p><p><strong>N° :</strong> %s</p>',
                htmlspecialchars($user->getPrenom() . ' ' . $user->getNom()),
                htmlspecialchars($user->getEmail()),
                htmlspecialchars($commande->getNumeroCommande())
            )
            : sprintf(
                '<p>Bonjour <strong>%s</strong>,</p><p>Le statut de votre commande <strong>%s</strong> a été mis à jour :</p>',
                htmlspecialchars($user->getPrenom()),
                htmlspecialchars($commande->getNumeroCommande())
            );

        $statut = sprintf(
            '<p style="font-size: 1.2em;">Nouveau statut : <strong>%s</strong></p>',
            htmlspecialchars($labelStatut)
        );

        $details = sprintf(
            '<ul>
                <li><strong>Menu :</strong> %s</li>
                <li><strong>Date de prestation :</strong> %s</li>
                <li><strong>Heure :</strong> %s</li>
            </ul>',
            htmlspecialchars($menu->getTitre()),
            $commande->getDatePrestation()->format(self::DATE_FORMAT),
            htmlspecialchars($commande->getHeureLivraison())
        );

        return sprintf('<h2>%s</h2>%s%s%s%s', $titre, $intro, $statut, $details, $this->getSignature());
    }

    /**
     * Signature commune à tous les emails.
     */
    private function getSignature(): string
    {
        return '<p>Cordialement,<br>L\'équipe Vite &amp; Gourmand</p>';
    }

    /**
     * Envoie un email à tous les gestionnaires (ROLE_SALARIE + ROLE_ADMIN).
     */
    private function notifierGestionnaires(string $sujet, string $corpsHtml): void
    {
        $gestionnaires = $this->utilisateurRepository->findGestionnaires();

        foreach ($gestionnaires as $gestionnaire) {
            $email = (new Email())
                ->from(self::EXPEDITEUR)
                ->to($gestionnaire->getEmail())
                ->subject('[STAFF] ' . $sujet)
                ->html($corpsHtml);

            $this->mailer->send($email);
        }
    }
}
