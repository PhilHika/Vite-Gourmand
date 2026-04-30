<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — AvisTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester la logique INTERNE de l'entité Avis sans base de données.
 * On teste les règles métier que le code enforce lui-même (les setters
 * qui lancent des exceptions, les valeurs par défaut, etc.)
 *
 * TYPE DE TEST : Unitaire (Unit Test)
 * → Aucune BDD, aucun conteneur Symfony, aucun service externe.
 * → On instancie directement l'entité avec "new Avis()" et c'est tout.
 * → Rapide, isolé, fiable.
 *
 * CONCEPTS PHPUNIT ILLUSTRÉS :
 *   - extend TestCase          → classe de base obligatoire
 *   - setUp()                  → méthode exécutée AVANT chaque test
 *   - assertEquals/assertSame  → vérifier une valeur
 *   - assertNull               → vérifier qu'une valeur est null
 *   - expectException()        → indiquer qu'une exception est attendue
 *   - expectExceptionMessage() → vérifier le message de l'exception
 *   - #[DataProvider]          → paramétrer un test avec plusieurs jeux de données
 *
 * #[DataProvider] vs foreach — POURQUOI C'EST MIEUX :
 *   Avec foreach : si le premier statut plante, les suivants ne sont PAS testés.
 *   Avec DataProvider : chaque cas est un test indépendant. Tous s'exécutent,
 *   tous apparaissent dans le rapport. Diagnostic plus précis.
 *
 * STRUCTURE :
 *   Groupe 1 : Valeurs par défaut à l'instanciation
 *   Groupe 2 : setDescription() — validation d'entrée
 *   Groupe 3 : setStatut()      — validation d'entrée
 *   Groupe 4 : setNote()        — comportement basique
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Avis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AvisTest extends TestCase
{
    /**
     * $avis est partagé entre tous les tests de ce fichier.
     * setUp() est appelé automatiquement par PHPUnit AVANT chaque test.
     * Chaque test repart donc d'un objet neuf → pas d'effets de bord entre tests.
     */
    private Avis $avis;

    protected function setUp(): void
    {
        // On crée un nouvel Avis propre avant chaque test
        $this->avis = new Avis();
    }

    // ============================================================
    // GROUPE 1 — Valeurs par défaut à l'instanciation
    // ============================================================
    // Pourquoi tester les valeurs par défaut ?
    // → Pour s'assurer que le code ne "dérive" pas au fil des commits.
    //   Un développeur modifie la propriété $statut dans l'entité ?
    //   Ce test détecte immédiatement la régression.
    // ============================================================

    /**
     * L'entité Avis définit : private ?string $statut = self::STATUT_EN_ATTENTE;
     * Ce test vérifie que cette valeur par défaut est bien 'en_attente'.
     */
    public function testStatutInitialEstEnAttente(): void
    {
        // assertSame() vérifie la valeur ET le type (strict)
        // Ici on s'assure que le statut par défaut est exactement la constante
        $this->assertSame(Avis::STATUT_EN_ATTENTE, $this->avis->getStatut());
    }

    /**
     * Les champs sans valeur par défaut doivent retourner null à l'instanciation.
     * Tester null évite des erreurs "Cannot access property on null" plus tard.
     */
    public function testNoteInitialeEstNull(): void
    {
        // assertNull() vérifie explicitement que la valeur est null
        $this->assertNull($this->avis->getNote());
    }

    public function testDescriptionInitialeEstNull(): void
    {
        $this->assertNull($this->avis->getDescription());
    }

    // ============================================================
    // GROUPE 2 — setDescription() : validation d'entrée
    // ============================================================
    // Pourquoi tester les exceptions ?
    // → setDescription() contient une règle métier : une description vide
    //   est interdite. Si quelqu'un supprime ce check par accident, les
    //   tests échouent immédiatement.
    //
    // RÈGLE PHPUNIT : expectException* doit TOUJOURS être placé
    // AVANT l'appel qui déclenche l'exception. PHPUnit enregistre
    // ce qu'il doit capturer, puis observe.
    // ============================================================

    /**
     * Cas nominal : une description valide est acceptée et stockée.
     */
    public function testSetDescriptionValide(): void
    {
        $description = 'Excellent service, très professionnel.';

        $resultat = $this->avis->setDescription($description);

        // La description est bien stockée
        $this->assertSame($description, $this->avis->getDescription());

        // setDescription() retourne "static" (fluent interface)
        // On vérifie que l'objet retourné est bien le même Avis
        $this->assertSame($this->avis, $resultat);
    }

    /**
     * Cas limite : une chaîne vide "" doit lever une exception.
     *
     * Séquence obligatoire :
     *   1. expectException()        → dire à PHPUnit : "j'attends cette exception"
     *   2. expectExceptionMessage() → dire à PHPUnit : "le message doit contenir ce texte"
     *   3. L'appel déclencheur      → PHPUnit intercepte et compare
     *
     * Note : expectExceptionMessage() vérifie que le message CONTIENT la chaîne
     * (pas une égalité exacte). "vide" suffit à matcher "ne peut pas être vide."
     */
    public function testSetDescriptionVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->avis->setDescription(''); // ← déclencheur (doit lever l'exception)
    }

    /**
     * Cas limite : des espaces seulement. Le code fait trim() avant de tester empty().
     * Ce test vérifie que "   " (espaces) est traité comme vide.
     * C'est un cas qu'on oublie souvent de tester → d'où son importance.
     */
    public function testSetDescriptionEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->avis->setDescription('   '); // espaces seulement → considéré vide
    }

    // ============================================================
    // GROUPE 3 — setStatut() : validation d'entrée
    // ============================================================

    /**
     * Cas nominal : un statut valide est accepté.
     * On utilise la constante de la classe pour éviter les typos.
     */
    public function testSetStatutValide(): void
    {
        $this->avis->setStatut(Avis::STATUT_PUBLIE);

        $this->assertSame(Avis::STATUT_PUBLIE, $this->avis->getStatut());
    }

    /**
     * Cas limite : statut vide → exception.
     * Même règle que setDescription() : le statut ne peut pas être une chaîne vide.
     */
    public function testSetStatutVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut ne peut pas être vide');

        $this->avis->setStatut('');
    }

    /**
     * Cas limite : espaces seulement → exception.
     * Le code fait trim() avant de tester empty() → "   " est traité comme vide.
     * Même cas que testSetDescriptionEspacesSeulsLeveException() mais pour le statut.
     */
    public function testSetStatutEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut ne peut pas être vide');

        $this->avis->setStatut('   ');
    }

    // ── AVANT (foreach) : ─────────────────────────────────────────────────────
    // public function testTousLesStatutsValidesSontAcceptes(): void
    // {
    //     foreach ([Avis::STATUT_EN_ATTENTE, ...] as $statut) {
    //         $this->avis->setStatut($statut);
    //         $this->assertSame($statut, $this->avis->getStatut());
    //     }
    // }
    // Problème : si STATUT_EN_ATTENTE plante, STATUT_PUBLIE et STATUT_REFUSE
    // ne sont jamais testés. Le rapport dit "1 failure" sans préciser lequel.
    //
    // APRÈS (DataProvider) : chaque statut est un test indépendant.
    // Si STATUT_PUBLIE plante, les autres continuent. Tous apparaissent.
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Provider des statuts valides. DOIT être public static (PHPUnit 10+).
     * Les clés du tableau ('en attente', 'publié', 'refusé') s'affichent
     * dans le rapport --testdox pour identifier précisément chaque cas.
     */
    public static function statutsValidesProvider(): array
    {
        return [
            'en attente' => [Avis::STATUT_EN_ATTENTE],
            'publié'     => [Avis::STATUT_PUBLIE],
            'refusé'     => [Avis::STATUT_REFUSE],
        ];
    }

    /**
     * Vérifie que chaque statut valide est accepté par setStatut().
     * Si une constante change de valeur, le cas correspondant échoue.
     *
     * #[DataProvider('statutsValidesProvider')] :
     *   PHPUnit injecte $statut depuis chaque ligne du provider.
     *   3 entrées = 3 tests indépendants dans le rapport.
     */
    #[DataProvider('statutsValidesProvider')]
    public function testSetStatutValideEstAccepte(string $statut): void
    {
        $this->avis->setStatut($statut);

        $this->assertSame($statut, $this->avis->getStatut());
    }

    // ============================================================
    // GROUPE 4 — setNote() : comportement basique
    // ============================================================

    /**
     * setNote() n'a pas de validation custom (pas de throw), juste un @Assert\Range
     * qui est vérifié par le Validator Symfony, pas par PHPUnit directement.
     * On teste donc juste que le setter stocke bien la valeur.
     */
    public function testSetNoteStockeLaValeur(): void
    {
        $this->avis->setNote(4);

        // assertEquals() : comparaison loose (int 4 == int 4) — ici équivalent à assertSame
        $this->assertEquals(4, $this->avis->getNote());
    }
}
