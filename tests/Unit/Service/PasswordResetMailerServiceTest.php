<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — PasswordResetMailerServiceTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester PasswordResetMailerService::envoyerLienReset() qui :
 *   1. Génère une URL absolue via UrlGeneratorInterface
 *   2. Envoie l'email avec cette URL au destinataire
 *
 * NOUVEAU CONCEPT : Mocker UrlGeneratorInterface
 *   On mocke aussi le générateur d'URL car en test unitaire,
 *   il n'y a pas de RequestStack disponible (pas de routeur réel).
 *   On contrôle ce que generate() retourne pour vérifier que
 *   l'URL générée est bien construite avec le BON token.
 *
 * NOUVEAU CONCEPT : with() pour vérifier les arguments du mock
 *   $urlGeneratorMock->method('generate')->with(
 *       'app_reset_password',                     // route
 *       $this->callback(fn($p) => $p['token'] === 'TOKEN_TEST'),
 *       UrlGeneratorInterface::ABSOLUTE_URL       // type
 *   )
 *   → Si quelqu'un casse le nom de route ou oublie ABSOLUTE_URL,
 *     le test échoue immédiatement.
 *
 * STRUCTURE :
 *   Groupe 1 : envoyerLienReset() — l'email est envoyé
 *   Groupe 2 : envoyerLienReset() — UrlGenerator reçoit les bons paramètres
 * ============================================================
 */

namespace App\Tests\Unit\Service;

use App\Entity\Utilisateur;
use App\Service\PasswordResetMailerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetMailerServiceTest extends TestCase
{
    private function creerUtilisateur(string $email): Utilisateur
    {
        $u = new Utilisateur();
        $u->setEmail($email);

        return $u;
    }

    // ============================================================
    // GROUPE 1 — Envoi de l'email
    // ============================================================

    /**
     * Cas nominal : un email est envoyé exactement une fois.
     *
     * expects($this->once()) :
     *   Plus strict que atLeastOnce() — vérifie qu'il y a UN seul envoi.
     *   Détecte un éventuel double-envoi accidentel.
     */
    public function testEnvoyerLienResetEnvoieUnEmail(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send');

        // UrlGenerator stubbé : on contrôle juste la valeur retournée
        $urlGeneratorStub = $this->createStub(UrlGeneratorInterface::class);
        $urlGeneratorStub->method('generate')
            ->willReturn('https://vite-et-gourmand.fr/reset-password/TOKEN_TEST');

        $projectDir = dirname(__DIR__, 3);
        $service = new PasswordResetMailerService($mailerMock, $urlGeneratorStub, $projectDir);

        $user = $this->creerUtilisateur('client@example.com');
        $service->envoyerLienReset($user, 'TOKEN_TEST');
    }

    // ============================================================
    // GROUPE 2 — Vérification des arguments passés à UrlGenerator
    // ============================================================

    /**
     * Le service appelle generate() avec :
     *   - route = 'app_reset_password'
     *   - parameters = ['token' => $token]
     *   - referenceType = ABSOLUTE_URL
     *
     * with() + expects() vérifient que CES arguments précis sont passés.
     * Si quelqu'un change la route en 'reset_password' (sans préfixe app_),
     * ce test détecte la régression immédiatement.
     */
    public function testEnvoyerLienResetAppelleUrlGeneratorAvecLesBonsParametres(): void
    {
        $token = 'abc-123-def-456-token-uuid';

        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $urlGeneratorMock->expects($this->once())
            ->method('generate')
            ->with(
                'app_reset_password',                       // nom de route exact
                ['token' => $token],                        // token transmis tel quel
                UrlGeneratorInterface::ABSOLUTE_URL         // URL absolue (pas relative)
            )
            ->willReturn('https://vite-et-gourmand.fr/reset-password/' . $token);

        // Le mailer est stubbé : on ne vérifie pas l'envoi ici, juste l'URL
        $mailerStub = $this->createStub(MailerInterface::class);

        $projectDir = dirname(__DIR__, 3);
        $service = new PasswordResetMailerService($mailerStub, $urlGeneratorMock, $projectDir);

        $service->envoyerLienReset($this->creerUtilisateur('user@example.com'), $token);
    }
}
