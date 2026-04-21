<?php

namespace App\Service;

use App\DTO\ContactData;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

class ContactMailerService
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';
    private const DESTINATAIRE_ADMIN = 'admin@vite-et-gourmand.fr';

    public function __construct(
        private MailerInterface $mailer,
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
     * Envoie le message du formulaire de contact à l'équipe.
     */
    public function envoyerMessageContact(ContactData $data): void
    {
        $this->mailer->send(
            $this->ajouterLogo(
                new TemplatedEmail()
                    ->from(self::EXPEDITEUR)
                    ->replyTo($data->email)
                    ->to(self::DESTINATAIRE_ADMIN)
                    ->subject('Contact : ' . $data->sujet)
                    ->htmlTemplate('emails/contact.html.twig')
                    ->context(['data' => $data])
            )
        );
    }
}
