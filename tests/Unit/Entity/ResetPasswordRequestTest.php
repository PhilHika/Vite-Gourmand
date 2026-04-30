<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — ResetPasswordRequestTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester l'entité ResetPasswordRequest, en particulier isExpired().
 * Cette méthode dépend de l'heure courante → problème classique en tests.
 *
 * POURQUOI C'EST INTÉRESSANT :
 *   isExpired() compare une date d'expiration à "maintenant".
 *   Si on écrit le test naïvement, il devient fragile (dépend du moment
 *   où il est exécuté). On résout ça avec des dates RELATIVES fixes.
 *
 * NOUVEAU CONCEPT PHPUNIT :
 *   → Tester du code TIME-DEPENDENT (dépendant de l'heure)
 *     Strategy : contrôler la date d'expiration dans le test
 *     plutôt que de dépendre de "now".
 *
 *   → assertInstanceOf() pour vérifier un type de retour
 *   → Tester un constructeur avec injection de dépendances
 *
 * RAPPEL ARCHITECTURE :
 *   ResetPasswordRequest a un constructeur EXPLICITE (pas de setters) :
 *     __construct(Utilisateur $utilisateur, string $token, DateTimeImmutable $expiresAt)
 *   → On doit créer un Utilisateur minimal pour l'instancier.
 *   → On contrôle expiresAt pour tester isExpired() de manière déterministe.
 *
 * STRUCTURE :
 *   Groupe 1 : Constructeur — initialisation correcte
 *   Groupe 2 : isExpired()  — logique temporelle
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\ResetPasswordRequest;
use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class ResetPasswordRequestTest extends TestCase
{
    /**
     * Méthode utilitaire : crée un Utilisateur minimal pour les tests.
     * ResetPasswordRequest exige un Utilisateur dans son constructeur.
     * On en crée un vide — pas besoin de données valides ici, on teste
     * uniquement ResetPasswordRequest, pas Utilisateur.
     */
    private function creerUtilisateurTest(): Utilisateur
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setEmail('test@example.com');

        return $utilisateur;
    }

    // ============================================================
    // GROUPE 1 — Constructeur : initialisation correcte
    // ============================================================
    // ResetPasswordRequest est une entité "immuable" (pas de setters).
    // Toutes les données sont passées au constructeur.
    // On vérifie que les getters retournent bien ce qui a été passé.
    // ============================================================

    /**
     * Le constructeur stocke correctement l'utilisateur et le token.
     */
    public function testConstructeurStockeLesValeurs(): void
    {
        $utilisateur = $this->creerUtilisateurTest();
        $token = 'abc123-token-uuid';
        $expiresAt = new \DateTimeImmutable('+1 hour'); // dans 1 heure

        $request = new ResetPasswordRequest($utilisateur, $token, $expiresAt);

        $this->assertSame($utilisateur, $request->getUtilisateur());
        $this->assertSame($token, $request->getToken());
        $this->assertSame($expiresAt, $request->getExpiresAt());
    }

    /**
     * requestedAt est initialisé AUTOMATIQUEMENT dans le constructeur.
     * On ne le passe pas en paramètre → il est créé avec "new DateTimeImmutable()".
     *
     * assertInstanceOf() : vérifie que le retour est bien un DateTimeImmutable.
     * On ne peut pas assertSame() sur une date "now" car elle change à chaque run.
     * On vérifie juste que c'est du bon type et qu'elle est récente.
     */
    public function testRequestedAtEstInitialiseAutomatiquement(): void
    {
        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            'token-test',
            new \DateTimeImmutable('+1 hour')
        );

        // assertInstanceOf : le type retourné est correct
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getRequestedAt());

        // La date de demande est dans les dernières 5 secondes (test rapide)
        $maintenant = new \DateTimeImmutable();
        $cinqSecondesAvant = $maintenant->modify('-5 seconds');

        // assertGreaterThanOrEqual : requestedAt >= il y a 5 secondes
        $this->assertGreaterThanOrEqual(
            $cinqSecondesAvant->getTimestamp(),
            $request->getRequestedAt()->getTimestamp()
        );
    }

    // ============================================================
    // GROUPE 2 — isExpired() : logique temporelle
    // ============================================================
    // Code source :
    //   return $this->expiresAt < new \DateTimeImmutable();
    //   // = "la date d'expiration est dans le passé"
    //
    // PROBLÈME : comment tester du code qui dépend de "maintenant" ?
    //
    // SOLUTION : contrôler expiresAt dans le test avec des dates RELATIVES.
    //   "+1 hour"  → dans le futur → isExpired() = false
    //   "-1 hour"  → dans le passé → isExpired() = true
    //   "-1 second" → vient de passer → isExpired() = true (cas limite)
    //
    // Cela rend les tests DÉTERMINISTES : ils passent toujours,
    // peu importe quand on les exécute.
    // ============================================================

    /**
     * Token valide : expiresAt dans 1 heure → isExpired() = false.
     *
     * new \DateTimeImmutable('+1 hour') = dans 1 heure à partir de maintenant.
     * Cette date sera toujours dans le futur quand le test s'exécute.
     */
    public function testIsExpiredTokenValide(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour'); // dans le futur

        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            'token-valide',
            $expiresAt
        );

        // assertFalse : le token N'EST PAS expiré
        $this->assertFalse($request->isExpired());
    }

    /**
     * Token expiré : expiresAt il y a 1 heure → isExpired() = true.
     *
     * new \DateTimeImmutable('-1 hour') = il y a 1 heure.
     * Cette date sera toujours dans le passé quand le test s'exécute.
     */
    public function testIsExpiredTokenExpire(): void
    {
        $expiresAt = new \DateTimeImmutable('-1 hour'); // dans le passé

        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            'token-expire',
            $expiresAt
        );

        // assertTrue : le token EST expiré
        $this->assertTrue($request->isExpired());
    }

    /**
     * Cas limite : expiré depuis 1 seconde seulement.
     * Même 1 seconde de délai = expiré. La comparaison est stricte (<).
     */
    public function testIsExpiredTokenExpireDepuisUneSeconde(): void
    {
        $expiresAt = new \DateTimeImmutable('-1 second');

        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            'token-presque-valide',
            $expiresAt
        );

        $this->assertTrue($request->isExpired());
    }

    /**
     * Cas limite : expire dans exactement 1 seconde → encore valide.
     */
    public function testIsExpiredTokenEncoreValideUneDerniereSeconde(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 second');

        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            'token-bientot-expire',
            $expiresAt
        );

        $this->assertFalse($request->isExpired());
    }

    /**
     * Scénario réel : token créé avec expiration dans 1h (comme le code prod).
     * Simule le flux complet : création → vérification immédiate → valide.
     */
    public function testScenarioCompletTokenCreePuisVerifie(): void
    {
        $token = bin2hex(random_bytes(16)); // token aléatoire comme en prod
        $expiresAt = new \DateTimeImmutable('+1 hour'); // expiration standard 1h

        $request = new ResetPasswordRequest(
            $this->creerUtilisateurTest(),
            $token,
            $expiresAt
        );

        // Juste créé → pas encore expiré
        $this->assertFalse($request->isExpired());

        // Le token est bien celui qu'on a fourni
        $this->assertSame($token, $request->getToken());

        // L'expiration est bien dans 1 heure (à ± 5 secondes d'imprécision)
        $differenceEnSecondes = $expiresAt->getTimestamp() - (new \DateTimeImmutable())->getTimestamp();
        $this->assertGreaterThan(3590, $differenceEnSecondes); // au moins 59min50s restantes
        $this->assertLessThanOrEqual(3600, $differenceEnSecondes); // pas plus d'1h
    }
}
