<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — HoraireExtensionTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester HoraireExtension::getHoraires(), qui contient une logique
 * métier non triviale : elle garantit que le résultat contient TOUJOURS
 * les 7 jours dans l'ordre (Lundi→Dimanche), avec null pour les jours
 * absents du MongoDB.
 *
 * POURQUOI C'EST IMPORTANT :
 *   Si quelqu'un modifie l'ordre des jours dans $jours[], ou change
 *   la clé d'indexation, le rendu des horaires en front sera silencieusement
 *   cassé. Ce test le détecte immédiatement.
 *
 * NOUVEAU CONCEPT : Mocker une classe concrète (pas une interface)
 *   HoraireRepository est une classe, pas une interface.
 *   createMock() fonctionne aussi sur les classes concrètes :
 *   PHPUnit génère une sous-classe anonyme qui surcharge toutes les méthodes.
 *   Le constructeur est bypassé → pas besoin de fournir ManagerRegistry.
 *
 * NOUVEAU CONCEPT : assertSame() sur des tableaux
 *   assertSame() est strict : compare les valeurs ET les types ET l'ordre des clés.
 *   Pour des tableaux associatifs ordonnés, c'est l'outil exact.
 *
 * STRUCTURE :
 *   Groupe 1 : Repository vide → tous les jours à null
 *   Groupe 2 : Données partielles → jours manquants à null
 *   Groupe 3 : Données complètes → toutes les valeurs présentes
 *   Groupe 4 : Ordre garanti → indépendant de l'ordre du repository
 * ============================================================
 */

namespace App\Tests\Unit\Twig;

use App\Document\Horaire;
use App\Repository\HoraireRepository;
use App\Twig\HoraireExtension;
use PHPUnit\Framework\TestCase;

class HoraireExtensionTest extends TestCase
{
    // Les 7 clés attendues dans l'ordre
    private const JOURS_ATTENDUS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    private function creerHoraire(string $jour, string $ouverture, string $fermeture): Horaire
    {
        $horaire = new Horaire();
        $horaire->setJour($jour);
        $horaire->setHeureOuverture($ouverture);
        $horaire->setHeureFermeture($fermeture);

        return $horaire;
    }

    private function creerExtensionAvec(array $horaires): HoraireExtension
    {
        // createStub() : on contrôle uniquement la valeur de retour de findAll().
        // Pas besoin de vérifier combien de fois elle est appelée → createStub()
        // évite la PHPUnit Notice "no expectations configured".
        // Le constructeur de HoraireRepository est bypassé automatiquement.
        $stub = $this->createStub(HoraireRepository::class);
        $stub->method('findAll')->willReturn($horaires);

        return new HoraireExtension($stub);
    }

    // ============================================================
    // GROUPE 1 — Repository vide → 7 clés à null
    // ============================================================

    /**
     * Même sans données MongoDB, le tableau retourné a toujours 7 entrées.
     * Cela garantit que le template Twig ne plante pas sur des clés manquantes.
     */
    public function testGetHorairesRepositoryVideRetourne7JoursANull(): void
    {
        $extension = $this->creerExtensionAvec([]);

        $result = $extension->getHoraires();

        $this->assertCount(7, $result);

        foreach (self::JOURS_ATTENDUS as $jour) {
            // assertArrayHasKey : vérifie qu'une clé existe dans le tableau
            $this->assertArrayHasKey($jour, $result);
            $this->assertNull($result[$jour]);
        }
    }

    // ============================================================
    // GROUPE 2 — Données partielles → jours manquants à null
    // ============================================================

    /**
     * Le restaurant est fermé le dimanche et le lundi.
     * Ces deux jours doivent apparaître dans le résultat avec null.
     */
    public function testGetHorairesJoursManquantsSontNull(): void
    {
        $horaires = [
            $this->creerHoraire('Mardi', '09:00', '18:00'),
            $this->creerHoraire('Mercredi', '09:00', '18:00'),
            $this->creerHoraire('Jeudi', '09:00', '18:00'),
            $this->creerHoraire('Vendredi', '09:00', '20:00'),
            $this->creerHoraire('Samedi', '10:00', '17:00'),
        ];

        $extension = $this->creerExtensionAvec($horaires);
        $result = $extension->getHoraires();

        // Toujours 7 clés
        $this->assertCount(7, $result);

        // Jours absents du repository → null dans le résultat
        $this->assertNull($result['Lundi']);
        $this->assertNull($result['Dimanche']);

        // Jours présents → données correctes
        $this->assertSame('09:00', $result['Mardi']['ouverture']);
        $this->assertSame('18:00', $result['Mardi']['fermeture']);
    }

    // ============================================================
    // GROUPE 3 — Données complètes → structure exacte vérifiée
    // ============================================================

    /**
     * Vérifie la structure exacte du tableau pour un jour donné.
     * Les clés du sous-tableau doivent être 'ouverture' et 'fermeture'.
     */
    public function testGetHorairesStructureDesEntreesEstCorrecte(): void
    {
        $horaires = [$this->creerHoraire('Lundi', '08:00', '19:30')];

        $extension = $this->creerExtensionAvec($horaires);
        $result = $extension->getHoraires();

        $entreeAttendue = [
            'ouverture' => '08:00',
            'fermeture' => '19:30',
        ];

        // assertSame sur tableaux : strict, vérifie ordre des clés aussi
        $this->assertSame($entreeAttendue, $result['Lundi']);
    }

    // ============================================================
    // GROUPE 4 — Ordre garanti : Lundi → Dimanche
    // ============================================================

    /**
     * Même si MongoDB retourne les horaires dans un ordre aléatoire,
     * le résultat doit toujours être indexé Lundi → Dimanche.
     *
     * array_keys() retourne les clés dans leur ordre d'insertion.
     */
    public function testGetHorairesOrdreToujoursLundiAuDimanche(): void
    {
        // Repository retourne dans un ordre "aléatoire" (Mercredi, Lundi, Samedi)
        $horaires = [
            $this->creerHoraire('Mercredi', '09:00', '18:00'),
            $this->creerHoraire('Lundi', '08:00', '17:00'),
            $this->creerHoraire('Samedi', '10:00', '16:00'),
        ];

        $extension = $this->creerExtensionAvec($horaires);
        $result = $extension->getHoraires();

        // L'ordre des clés doit toujours être Lundi → Dimanche
        $this->assertSame(self::JOURS_ATTENDUS, array_keys($result));
    }

    /**
     * Vérifie que getFunctions() enregistre bien la fonction Twig 'get_horaires'.
     */
    public function testGetFunctionsEnregistreGetHoraires(): void
    {
        $extension = $this->creerExtensionAvec([]);

        $fonctions = $extension->getFunctions();

        $this->assertCount(1, $fonctions);
        $this->assertSame('get_horaires', $fonctions[0]->getName());
    }
}
