<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetMailerService
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Envoie un email contenant le lien de réinitialisation de mot de passe.
     */
    public function envoyerLienReset(Utilisateur $user, string $token): void
    {
        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from(self::EXPEDITEUR)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->buildResetHtml($user, $resetUrl));

        $this->mailer->send($email);
    }

    private function buildResetHtml(Utilisateur $user, string $resetUrl): string
    {
        $prenom = htmlspecialchars($user->getPrenom());
        $url = htmlspecialchars($resetUrl);

        $bouton = '<a href="' . $url . '" style="display: inline-block; '
            . 'padding: 10px 20px; background-color: #0d6efd; '
            . 'color: #ffffff; text-decoration: none; '
            . 'border-radius: 5px;">Réinitialiser mon mot de passe</a>';

        return '<h2>Réinitialisation de mot de passe</h2>'
            . '<p>Bonjour <strong>' . $prenom . '</strong>,</p>'
            . '<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>'
            . '<p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>'
            . '<p>' . $bouton . '</p>'
            . '<p><small>Ce lien est valable pendant <strong>1 heure</strong>. '
            . 'Si vous n\'avez pas fait cette demande, ignorez cet email.</small></p>'
            . '<p>Cordialement,<br>L\'équipe Vite &amp; Gourmand</p>';
    }
}
