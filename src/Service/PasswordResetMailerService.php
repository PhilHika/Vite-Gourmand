<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Contract\PasswordResetMailerServiceInterface;

class PasswordResetMailerService implements PasswordResetMailerServiceInterface
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    /**
     * Ajoute le logo aux emails.
     */
    private function ajouterLogo(TemplatedEmail $email): TemplatedEmail
    {
        return $email->embedFromPath(
            $this->projectDir . '/public/images/VetG-logo.jpg',
            'logo'
        );
    }

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
            $this->ajouterLogo(
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context(['user' => $user, 'resetUrl' => $resetUrl])
            )
        );
    }
}
