<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — ContactMailerServiceTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester ContactMailerService::envoyerMessageContact() qui boucle
 * sur les gestionnaires (ROLE_SALARIE + ROLE_ADMIN) et envoie un
 * email à chacun.
 *
 * NOUVEAU CONCEPT : expects($this->exactly(N))
 *   On peut être plus strict que atLeastOnce() en spécifiant le nombre
 *   exact d'appels attendus. Ici : 3 gestionnaires → exactly(3) envois.
 *
 * NOUVEAU CONCEPT : expects($this->never()) sur la boucle
 *   Si findGestionnaires() retourne un tableau vide, send() ne doit
 *   jamais être appelé. C'est un cas limite important : pas de gestionnaires
 *   = pas d'email envoyé silencieusement (pas d'exception).
 *
 * STRUCTURE :
 *   Groupe 1 : Aucun gestionnaire → aucun email
 *   Groupe 2 : N gestionnaires → exactement N emails
 * ============================================================
 */

namespace App\Tests\Unit\Service;

use App\DTO\ContactData;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\ContactMailerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class ContactMailerServiceTest extends TestCase
{
    /**
     * Helper : crée un ContactData valide pour les tests.
     */
    private function creerContactData(): ContactData
    {
        $data = new ContactData();
        $data->nom = 'Jean Dupont';
        $data->email = 'jean.dupont@example.com';
        $data->code_postal = '75001';
        $data->sujet = 'Demande de devis';
        $data->message = 'Bonjour, je souhaiterais un devis pour 50 personnes.';

        return $data;
    }

    private function creerGestionnaire(string $email): Utilisateur
    {
        $u = new Utilisateur();
        $u->setEmail($email);

        return $u;
    }

    // ============================================================
    // GROUPE 1 — Aucun gestionnaire → aucun email envoyé
    // ============================================================

    /**
     * Cas limite : aucun gestionnaire en base → la boucle ne s'exécute pas.
     *
     * expects($this->never()) :
     *   PHPUnit vérifie que send() n'est appelé AUCUNE fois.
     *   Si la boucle envoie quand même un email → test échoue.
     */
    public function testEnvoyerMessageContactSansGestionnaireNEnvoieAucunEmail(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->never())
            ->method('send');

        // Repository retourne un tableau VIDE → la boucle foreach ne s'exécute pas
        $repositoryMock = $this->createStub(UtilisateurRepository::class);
        $repositoryMock->method('findGestionnaires')->willReturn([]);

        $projectDir = dirname(__DIR__, 3);
        $service = new ContactMailerService($mailerMock, $repositoryMock, $projectDir);

        $service->envoyerMessageContact($this->creerContactData());
    }

    // ============================================================
    // GROUPE 2 — N gestionnaires → exactement N emails
    // ============================================================

    /**
     * Cas nominal : 3 gestionnaires → 3 envois d'email.
     *
     * expects($this->exactly(3)) :
     *   PHPUnit vérifie que send() est appelé EXACTEMENT 3 fois.
     *   - 2 appels → fail
     *   - 4 appels → fail
     *   - 3 appels → pass ✅
     *
     * Plus strict que atLeastOnce() : détecte aussi les envois en TROP
     * (par exemple un double-envoi accidentel par bug).
     */
    public function testEnvoyerMessageContactEnvoieUnEmailParGestionnaire(): void
    {
        $gestionnaires = [
            $this->creerGestionnaire('admin@vite-et-gourmand.fr'),
            $this->creerGestionnaire('salarie1@vite-et-gourmand.fr'),
            $this->creerGestionnaire('salarie2@vite-et-gourmand.fr'),
        ];

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->exactly(3)) // exactement 3 envois
            ->method('send');

        $repositoryMock = $this->createStub(UtilisateurRepository::class);
        $repositoryMock->method('findGestionnaires')->willReturn($gestionnaires);

        $projectDir = dirname(__DIR__, 3);
        $service = new ContactMailerService($mailerMock, $repositoryMock, $projectDir);

        $service->envoyerMessageContact($this->creerContactData());
    }

    /**
     * Cas avec un seul gestionnaire → un seul email.
     * Vérifie que la boucle gère correctement N=1.
     */
    public function testEnvoyerMessageContactAvecUnSeulGestionnaire(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once()) // exactement 1 envoi
            ->method('send');

        $repositoryMock = $this->createStub(UtilisateurRepository::class);
        $repositoryMock->method('findGestionnaires')
            ->willReturn([$this->creerGestionnaire('admin@vite-et-gourmand.fr')]);

        $projectDir = dirname(__DIR__, 3);
        $service = new ContactMailerService($mailerMock, $repositoryMock, $projectDir);

        $service->envoyerMessageContact($this->creerContactData());
    }
}
