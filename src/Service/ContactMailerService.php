<?php

namespace App\Service;

use App\DTO\ContactData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactMailerService
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';
    private const DESTINATAIRE_ADMIN = 'admin@vite-et-gourmand.fr';

    public function __construct(
        private MailerInterface $mailer,
    ) {}

    /**
     * Envoie le message du formulaire de contact à l'équipe.
     */
    public function envoyerMessageContact(ContactData $data): void
    {
        $email = (new Email())
            ->from(self::EXPEDITEUR)
            ->replyTo($data->email)
            ->to(self::DESTINATAIRE_ADMIN)
            ->subject('Contact : ' . $data->sujet)
            ->text(sprintf(
                "Nouveau message de contact\n\nDe : %s (%s)\nCode postal : %s\nSujet : %s\n\nMessage :\n%s",
                $data->nom,
                $data->email,
                $data->code_postal,
                $data->sujet,
                $data->message
            ))
            ->html(sprintf(
                '<h2>Nouveau message de contact</h2>
                <p><strong>De :</strong> %s (%s)</p>
                <p><strong>Code postal :</strong> %s</p>
                <p><strong>Sujet :</strong> %s</p>
                <h3>Message :</h3>
                <p>%s</p>',
                htmlspecialchars($data->nom),
                htmlspecialchars($data->email),
                htmlspecialchars($data->code_postal),
                htmlspecialchars($data->sujet),
                nl2br(htmlspecialchars($data->message))
            ));

        $this->mailer->send($email);
    }
}
