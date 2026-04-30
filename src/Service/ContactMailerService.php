<?php

namespace App\Service;

use App\DTO\ContactData;
use App\Repository\UtilisateurRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use App\Contract\ContactMailerServiceInterface;

class ContactMailerService implements ContactMailerServiceInterface
{
    private const EXPEDITEUR = 'noreply@vite-et-gourmand.fr';

    public function __construct(
        private MailerInterface $mailer,
        private UtilisateurRepository $utilisateurRepository,
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
     * Envoie le message du formulaire de contact à tous les gestionnaires (ROLE_SALARIE + ROLE_ADMIN).
     */
    public function envoyerMessageContact(ContactData $data): void
    {
        foreach ($this->utilisateurRepository->findGestionnaires() as $gestionnaire) {
            $this->mailer->send(
                $this->ajouterLogo(
                    new TemplatedEmail()
                        ->from(self::EXPEDITEUR)
                        ->replyTo($data->email)
                        ->to($gestionnaire->getEmail())
                        ->subject('Contact : ' . $data->sujet)
                        ->htmlTemplate('emails/contact.html.twig')
                        ->context(['data' => $data])
                )
            );
        }
    }
}
