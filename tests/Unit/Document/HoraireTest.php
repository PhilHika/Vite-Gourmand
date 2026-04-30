<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — HoraireTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester le Document MongoDB Horaire. Ce document encapsule
 * une logique métier importante : les horaires d'ouverture
 * doivent respecter un format précis ET une cohérence temporelle
 * (fermeture APRÈS ouverture).
 *
 * TYPE DE TEST : Unitaire (Unit Test)
 * → Pas de MongoDB. On crée le Document directement avec "new Horaire()".
 * → Le Document est un POPO (Plain Old PHP Object) en dehors de Doctrine ODM.
 *
 * CONCEPTS PHPUNIT ILLUSTRÉS :
 *   - expectException()        → capter une exception
 *   - expectExceptionMessage() → vérifier le message (substring)
 *   - assertSame()             → comparaison stricte
 *   - Tester la DÉPENDANCE D'ORDRE entre setters
 *     (setHeureFermeture dépend de setHeureOuverture)
 *
 * POINT IMPORTANT — Dépendance d'ordre :
 *   setHeureFermeture() vérifie que la fermeture est après l'ouverture.
 *   Mais cette vérification n'est faite QUE si heureOuverture est déjà définie.
 *   → Dans certains tests, il faut d'abord appeler setHeureOuverture().
 *
 * STRUCTURE :
 *   Groupe 1 : setJour()            — validation de la valeur du jour
 *   Groupe 2 : setHeureOuverture()  — validation du format HH:mm
 *   Groupe 3 : setHeureFermeture()  — validation format + cohérence temporelle
 *   Groupe 4 : Scénario complet     — création d'un horaire valide complet
 * ============================================================
 */

namespace App\Tests\Unit\Document;

use App\Document\Horaire;
use PHPUnit\Framework\TestCase;

class HoraireTest extends TestCase
{
    private Horaire $horaire;

    protected function setUp(): void
    {
        $this->horaire = new Horaire();
    }

    // ============================================================
    // GROUPE 1 — setJour() : validation de la valeur du jour
    // ============================================================
    // setJour() vérifie que le jour est dans la liste des 7 jours valides.
    // Si le jour n'est pas dans la liste → InvalidArgumentException.
    //
    // Message de l'exception (sprintf) :
    //   'Le jour "Funday" n\'est pas un jour de la semaine valide.'
    //
    // On utilise expectExceptionMessage() avec une PARTIE du message
    // pour éviter de copier-coller le message exact (fragile).
    // ============================================================

    /**
     * Les 7 jours valides doivent tous être acceptés.
     * On utilise une boucle pour éviter de répéter 7 tests identiques.
     *
     * IMPORTANT : dans une boucle, si une itération échoue, PHPUnit
     * s'arrête. Le message de assertSame() avec le 3ème paramètre aide
     * à identifier quelle itération a échoué.
     */
    public function testSetJourTousLesJoursValidesAcceptes(): void
    {
        $joursValides = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        foreach ($joursValides as $jour) {
            $this->horaire->setJour($jour);

            // 3ème paramètre de assertSame = message affiché si le test échoue
            // Très utile dans les boucles pour identifier quelle valeur a causé l'échec
            $this->assertSame($jour, $this->horaire->getJour(), "Le jour '$jour' aurait dû être accepté.");
        }
    }

    /**
     * Un jour inventé → exception.
     *
     * expectExceptionMessage('"Funday"') :
     *   On vérifie que le message contient "Funday" entre guillemets.
     *   Le message réel est : 'Le jour "Funday" n'est pas un jour de la semaine valide.'
     *   La substring '"Funday"' est assez spécifique pour confirmer que c'est
     *   la bonne exception sans dupliquer tout le message.
     */
    public function testSetJourInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"Funday"'); // substring du message sprintf

        $this->horaire->setJour('Funday');
    }

    /**
     * La casse est sensible : 'lundi' (minuscule) est différent de 'Lundi'.
     * Ce test vérifie que la validation ne fait pas de comparaison insensible à la casse.
     */
    public function testSetJourCasseInvalideLevException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"lundi"'); // 'lundi' minuscule n'est pas valide

        $this->horaire->setJour('lundi'); // doit être 'Lundi'
    }

    // ============================================================
    // GROUPE 2 — setHeureOuverture() : validation du format HH:mm
    // ============================================================
    // Regex du code source : /^[0-2][0-9]:[0-5][0-9]$/
    //
    // Format valide  : "09:30", "18:00", "23:59"
    // Format invalide: "9:30" (sans zéro), "930", "09h30", "25:00"
    // ============================================================

    /**
     * Format valide → heure stockée correctement.
     */
    public function testSetHeureOuvertureFormatValide(): void
    {
        $this->horaire->setHeureOuverture('09:00');

        $this->assertSame('09:00', $this->horaire->getHeureOuverture());
    }

    /**
     * Format sans zéro initial → invalide ("9:30" vs "09:30").
     * La regex impose [0-2] comme premier caractère, donc "9" seul est refusé.
     */
    public function testSetHeureOuvertureFormatSansZeroInitialLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('format de l\'heure d\'ouverture');

        $this->horaire->setHeureOuverture('9:30'); // manque le zéro initial
    }

    /**
     * Format sans séparateur → invalide.
     */
    public function testSetHeureOuvertureFormatSansDeuxPointsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('format de l\'heure d\'ouverture');

        $this->horaire->setHeureOuverture('0930'); // pas de ":"
    }

    /**
     * Séparateur "h" au lieu de ":" → invalide.
     */
    public function testSetHeureOuvertureFormatAvecHLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->horaire->setHeureOuverture('09h30');
    }

    // ============================================================
    // GROUPE 3 — setHeureFermeture() : format + cohérence temporelle
    // ============================================================
    // setHeureFermeture() fait DEUX vérifications :
    //   1. Le format HH:mm (même regex que l'ouverture)
    //   2. La fermeture est STRICTEMENT après l'ouverture
    //      (uniquement si heureOuverture est déjà définie)
    //
    // DÉPENDANCE D'ORDRE :
    //   Pour tester la cohérence temporelle, il FAUT d'abord définir
    //   l'heureOuverture. Sinon, la vérification est sautée (car null).
    //
    //   Code source :
    //   if ($this->heureOuverture !== null && $heureFermeture <= $this->heureOuverture)
    //       throw new InvalidArgumentException(...)
    // ============================================================

    /**
     * Format valide + heure après ouverture → accepté.
     * Ordre obligatoire : d'abord setHeureOuverture(), puis setHeureFermeture().
     */
    public function testSetHeureFermetureValideApresOuverture(): void
    {
        $this->horaire->setHeureOuverture('09:00'); // doit être définie en premier
        $this->horaire->setHeureFermeture('18:30');

        $this->assertSame('18:30', $this->horaire->getHeureFermeture());
    }

    /**
     * Format invalide → exception (même règle que setHeureOuverture).
     */
    public function testSetHeureFermetureFormatInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('format de l\'heure de fermeture');

        $this->horaire->setHeureFermeture('18h30'); // format incorrect
    }

    /**
     * Fermeture AVANT ouverture → exception.
     *
     * Scénario : ouverture à 09:00, fermeture à 08:00
     * → Logiquement impossible, le code doit le rejeter.
     */
    public function testSetHeureFermetureAvantOuvertureLeveException(): void
    {
        $this->horaire->setHeureOuverture('09:00'); // définir l'ouverture d'abord

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('doit être après l\'heure d\'ouverture');

        $this->horaire->setHeureFermeture('08:00'); // AVANT l'ouverture → exception
    }

    /**
     * Fermeture ÉGALE à l'ouverture → exception.
     *
     * La condition utilise "<=" donc l'égalité est aussi refusée.
     * "Ouvert de 09:00 à 09:00" n'a aucun sens.
     */
    public function testSetHeureFermetureEgaleOuvertureLeveException(): void
    {
        $this->horaire->setHeureOuverture('09:00');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('doit être après l\'heure d\'ouverture');

        $this->horaire->setHeureFermeture('09:00'); // même heure → refusé (strictement après)
    }

    /**
     * Cas limite : fermeture 1 minute après l'ouverture → valide.
     * Vérifie que la borne inférieure est correcte (> strict, pas >=).
     */
    public function testSetHeureFermetureUneMinuteApresOuverture(): void
    {
        $this->horaire->setHeureOuverture('09:00');
        $this->horaire->setHeureFermeture('09:01'); // 1 minute après → valide

        $this->assertSame('09:01', $this->horaire->getHeureFermeture());
    }

    // ============================================================
    // GROUPE 4 — Scénario complet : créer un horaire valide
    // ============================================================
    // Un test qui simule un cas d'usage réel complet.
    // Utile pour valider que toutes les règles fonctionnent ensemble.
    // ============================================================

    /**
     * Création d'un horaire complet valide : Lundi de 09:00 à 18:30.
     * Ce test vérifie l'intégration de toutes les règles ensemble.
     */
    public function testCreationHoraireCompletValide(): void
    {
        $this->horaire->setJour('Lundi');
        $this->horaire->setHeureOuverture('09:00');
        $this->horaire->setHeureFermeture('18:30');

        // Vérification de chaque propriété
        $this->assertSame('Lundi', $this->horaire->getJour());
        $this->assertSame('09:00', $this->horaire->getHeureOuverture());
        $this->assertSame('18:30', $this->horaire->getHeureFermeture());
    }
}
