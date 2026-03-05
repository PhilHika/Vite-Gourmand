<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
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
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->mailer->send(
            (new TemplatedEmail())
                ->from(self::EXPEDITEUR)
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('emails/reset_password.html.twig')
                ->context(['user' => $user, 'resetUrl' => $resetUrl])
        );
    }
}
