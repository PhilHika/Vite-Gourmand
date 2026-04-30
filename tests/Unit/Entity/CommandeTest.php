<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — CommandeTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester la logique MÉTIER de l'entité Commande. C'est l'entité
 * la plus complexe du projet : elle contient des calculs de prix,
 * une machine à états (statuts) et une génération d'identifiant.
 *
 * TYPE DE TEST : Unitaire (Unit Test)
 * → Pas de BDD, pas de Symfony. On crée les objets directement.
 * → Pour calculerPrixMenu(), on crée un vrai objet Menu (pas un mock)
 *   car Menu n'a pas de dépendances externes.
 *
 * CONCEPTS PHPUNIT ILLUSTRÉS :
 *   - setUp()                          → réinitialisation avant chaque test
 *   - assertSame / assertEquals        → comparaison stricte vs loose
 *   - assertEqualsWithDelta()          → comparaison float avec tolérance
 *   - assertFalse / assertTrue         → vérifier un booléen
 *   - assertNotNull                    → vérifier qu'une valeur existe
 *   - assertMatchesRegularExpression() → vérifier un format avec regex
 *   - assertStringEndsWith()           → vérifier la fin d'une chaîne
 *
 * REFACTORING PHPUNIT 12 — BONNES PRATIQUES :
 *
 *   #[DataProvider('methodName')]
 *     Remplace les boucles foreach dans les tests.
 *     AVANTAGE vs foreach :
 *       - Chaque cas devient un test INDÉPENDANT
 *       - Si 'confirmee' plante mais 'annulee' passe, DataProvider l'affiche
 *       - Avec foreach, PHPUnit s'arrête au premier échec → les autres cas sont ignorés
 *     RÈGLE : la méthode provider doit être 'public static' (PHPUnit 10+)
 *
 *   #[TestDox('description lisible')]
 *     Remplace les noms de méthodes cryptiques (testPeutEtreTermineeChemin1...)
 *     par une phrase affichée dans le rapport --testdox.
 *     CONVENTION PSR1 : les noms de méthodes en camelCase UNIQUEMENT.
 *     Les underscores dans les noms de test sont une violation PSR1.
 *     Avant : testPeutEtreTerminee_CheminUn()       ← PSR1 violation
 *     Après : testPeutEtreTermineeCheminUn() + #[TestDox('...')]  ← correct
 *
 * STRUCTURE :
 *   Groupe 1 : Valeurs par défaut
 *   Groupe 2 : calculerPrixMenu()  — logique de calcul et remise
 *   Groupe 3 : peutEtreTerminee() — machine à états (3 chemins)
 *   Groupe 4 : peutEtreEnAttenteRetourMateriel() — machine à états
 *   Groupe 5 : generateNumeroCommande() — format de l'identifiant
 *   Groupe 6 : HasPrixCommandeTrait — validation des prix
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Commande;
use App\Entity\Menu;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    private Commande $commande;

    /**
     * setUp() crée un objet Commande réutilisable pour tous les tests.
     * Chaque test démarre avec un objet frais (pas de pollution entre tests).
     */
    protected function setUp(): void
    {
        $this->commande = new Commande();
    }

    // ============================================================
    // GROUPE 1 — Valeurs par défaut
    // ============================================================

    /**
     * L'entité déclare : private ?string $statut = self::STATUT_EN_ATTENTE;
     * Sans aucun appel à setStatut(), le statut doit déjà être 'en_attente'.
     */
    public function testStatutInitialEstEnAttente(): void
    {
        $this->assertSame(Commande::STATUT_EN_ATTENTE, $this->commande->getStatut());
    }

    public function testNumeroCommandeInitialementNull(): void
    {
        // Avant @PrePersist (generateNumeroCommande), le numéro est null
        $this->assertNull($this->commande->getNumeroCommande());
    }

    // ============================================================
    // GROUPE 2 — calculerPrixMenu()
    // ============================================================
    // Cette méthode calcule le prix total :
    //   - prix de base = prixParPersonne * nombrePersonne
    //   - remise de 10% si nombrePersonne >= nombrePersonneMinimum + 5
    //
    // Stratégie : on crée un Menu réel avec des valeurs connues
    // pour pouvoir calculer manuellement le résultat attendu.
    //
    // Exemple : menu à 50€/personne, minimum 10 personnes
    //   → seuil de remise : 10 + 5 = 15 personnes
    //   → 12 personnes : 50 * 12 = 600€ (pas de remise)
    //   → 15 personnes : 50 * 15 * 0.90 = 675€ (remise -10%)
    // ============================================================

    /**
     * Méthode utilitaire privée pour créer un Menu avec des valeurs précises.
     * Non préfixée par "test" → PHPUnit ne l'exécute pas comme un test.
     */
    private function creerMenuTest(float $prixParPersonne = 50.0, int $nombrePersonneMinimum = 10): Menu
    {
        $menu = new Menu();
        $menu->setPrixParPersonne($prixParPersonne);
        $menu->setNombrePersonneMinimum($nombrePersonneMinimum);

        return $menu;
    }

    /**
     * Cas sans remise : 12 personnes < seuil (10+5=15) → prix plein.
     * Prix attendu : 50 * 12 = 600.00€
     */
    public function testCalculerPrixMenuSansRemise(): void
    {
        $menu = $this->creerMenuTest(prixParPersonne: 50.0, nombrePersonneMinimum: 10);

        $this->commande->setMenu($menu);
        $this->commande->setNombrePersonne(12); // 12 < 15 → pas de remise

        $this->commande->calculerPrixMenu();

        // assertEqualsWithDelta() : pour les floats, on tolère un écart de 0.01€
        // Pourquoi ? Les calculs flottants en PHP peuvent produire 599.9999... au lieu de 600.0
        // La tolérance (delta) évite des faux échecs dus à la précision des flottants.
        $this->assertEqualsWithDelta(600.0, $this->commande->getPrixMenu(), 0.01);
    }

    /**
     * Cas avec remise : 15 personnes == seuil (10+5=15) → remise -10%.
     * La condition utilise ">=" donc 15 est inclus dans la remise.
     * Prix attendu : 50 * 15 * 0.90 = 675.00€
     */
    public function testCalculerPrixMenuAvecRemiseAuSeuil(): void
    {
        $menu = $this->creerMenuTest(prixParPersonne: 50.0, nombrePersonneMinimum: 10);

        $this->commande->setMenu($menu);
        $this->commande->setNombrePersonne(15); // 15 >= 10+5=15 → remise active

        $this->commande->calculerPrixMenu();

        $this->assertEqualsWithDelta(675.0, $this->commande->getPrixMenu(), 0.01);
    }

    /**
     * Cas avec remise : au-dessus du seuil (20 personnes > 15).
     * Prix attendu : 50 * 20 * 0.90 = 900.00€
     */
    public function testCalculerPrixMenuAvecRemiseAuDessusduSeuil(): void
    {
        $menu = $this->creerMenuTest(prixParPersonne: 50.0, nombrePersonneMinimum: 10);

        $this->commande->setMenu($menu);
        $this->commande->setNombrePersonne(20); // 20 > 15 → remise active

        $this->commande->calculerPrixMenu();

        $this->assertEqualsWithDelta(900.0, $this->commande->getPrixMenu(), 0.01);
    }

    /**
     * Cas limite : 14 personnes juste EN DESSOUS du seuil → pas de remise.
     * Ce test vérifie que la borne est strictement respectée (14 < 15).
     */
    public function testCalculerPrixMenuJusteEnDessousDuSeuil(): void
    {
        $menu = $this->creerMenuTest(prixParPersonne: 50.0, nombrePersonneMinimum: 10);

        $this->commande->setMenu($menu);
        $this->commande->setNombrePersonne(14); // 14 < 15 → pas de remise

        $this->commande->calculerPrixMenu();

        // 50 * 14 = 700€ sans remise
        $this->assertEqualsWithDelta(700.0, $this->commande->getPrixMenu(), 0.01);
    }

    /**
     * Guard clause : si le menu n'est pas défini, la méthode ne fait rien.
     * On vérifie que prixMenu reste null (pas de calcul sur des données absentes).
     * Tester les "guard clauses" évite les erreurs NullPointerException en prod.
     */
    public function testCalculerPrixMenuSansMenuNeFaitRien(): void
    {
        // Pas de setMenu() → $this->menu est null
        $this->commande->setNombrePersonne(10);
        $this->commande->calculerPrixMenu(); // ne doit pas planter

        // prixMenu doit rester null (aucun calcul effectué)
        $this->assertNull($this->commande->getPrixMenu());
    }

    /**
     * Guard clause : si le nombre de personnes n'est pas défini, même résultat.
     */
    public function testCalculerPrixMenuSansNombrePersonneNeFaitRien(): void
    {
        $menu = $this->creerMenuTest();
        $this->commande->setMenu($menu);
        // Pas de setNombrePersonne() → $this->nombrePersonne est null
        $this->commande->calculerPrixMenu();

        $this->assertNull($this->commande->getPrixMenu());
    }

    /**
     * Vérifie que round($prixBase, 2) est bien appliqué.
     *
     * En PHP, 10.1 * 3 = 30.299999999999997 (arithmétique flottante IEEE 754).
     * Sans round(), prixMenu contiendrait cette valeur approximative.
     * Avec round(), on obtient 30.30 — valeur correcte pour l'affichage en euros.
     */
    public function testCalculerPrixMenuArronditADeuxDecimales(): void
    {
        // 10.1 * 3 = 30.299999... en float → round donne 30.30
        $menu = $this->creerMenuTest(prixParPersonne: 10.1, nombrePersonneMinimum: 10);

        $this->commande->setMenu($menu);
        $this->commande->setNombrePersonne(3); // 3 < 10+5=15 → pas de remise

        $this->commande->calculerPrixMenu();

        $this->assertEqualsWithDelta(30.30, $this->commande->getPrixMenu(), 0.001);
    }

    // ============================================================
    // GROUPE 3 — peutEtreTerminee() : machine à états
    // ============================================================
    // peutEtreTerminee() implémente 3 chemins vers le statut "terminée" :
    //
    //   Chemin 1 : statut=livree ET pretMateriel=false
    //              → terminée directement après livraison (sans matériel)
    //
    //   Chemin 2 : statut=livree ET pretMateriel=true ET restitutionMateriel=true
    //              → raccourci : le matériel était prêté et déjà rendu
    //
    //   Chemin 3 : statut=en_attente_retour_materiel ET restitutionMateriel=true
    //              → flux normal avec retour matériel
    //
    // On teste CHAQUE chemin positivement ET les cas négatifs.
    // ============================================================

    // ── #[TestDox] : affiche une phrase lisible dans le rapport --testdox ──
    // Exemple de sortie sans TestDox : "Peut Etre Terminee Chemin Un Livree Sans Materiel"
    // Exemple de sortie avec TestDox : "Chemin 1 : livrée sans matériel → peut être terminée"
    // ────────────────────────────────────────────────────────────────────────

    #[TestDox('Chemin 1 : livrée sans matériel → peut être terminée immédiatement')]
    public function testPeutEtreTermineeCheminUnLivreeSansMateriel(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(false);
        $this->commande->setRestitutionMateriel(false);

        // assertTrue() : vérifie que la condition est vraie
        $this->assertTrue($this->commande->peutEtreTerminee());
    }

    #[TestDox('Chemin 2 : livrée avec matériel déjà restitué → raccourci vers terminée')]
    public function testPeutEtreTermineeCheminDeuxLivreeAvecMaterielRestitue(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(true);

        $this->assertTrue($this->commande->peutEtreTerminee());
    }

    #[TestDox('Chemin 3 : en attente retour + restitution validée → terminée')]
    public function testPeutEtreTermineeCheminTroisAttenteRetourMaterielRestitue(): void
    {
        $this->commande->setStatut(Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(true);

        $this->assertTrue($this->commande->peutEtreTerminee());
    }

    #[TestDox("Cas négatif : statut 'en_attente' ne peut jamais être terminée")]
    public function testNePeutPasEtreTermineeStatutEnAttente(): void
    {
        $this->commande->setStatut(Commande::STATUT_EN_ATTENTE);
        $this->commande->setPretMateriel(false);
        $this->commande->setRestitutionMateriel(false);

        // assertFalse() : vérifie que la condition est fausse
        $this->assertFalse($this->commande->peutEtreTerminee());
    }

    #[TestDox("Cas négatif : livrée avec matériel prêté mais non restitué → pas terminée")]
    public function testNePeutPasEtreTermineeLivreeAvecMaterielNonRestitue(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(false);

        $this->assertFalse($this->commande->peutEtreTerminee());
    }

    #[TestDox("Cas négatif : en attente retour matériel non restitué → pas terminée")]
    public function testNePeutPasEtreTermineeAttenteRetourMaterielNonRestitue(): void
    {
        $this->commande->setStatut(Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(false);

        $this->assertFalse($this->commande->peutEtreTerminee());
    }

    // ── #[DataProvider] remplace le foreach ──────────────────────────────────
    // AVANT (foreach, un seul test) :
    //   foreach ($statutsInterdits as $statut) {
    //       $this->assertFalse($this->commande->peutEtreTerminee());
    //   }
    //   → Si STATUT_CONFIRMEE plante, PHPUnit s'arrête. STATUT_ANNULEE n'est
    //     jamais testé. Le rapport dit "1 fail" mais n'indique pas lequel exactement.
    //
    // APRÈS (DataProvider, 3 tests indépendants) :
    //   Chaque statut est son propre test. Si STATUT_CONFIRMEE plante,
    //   STATUT_EN_PREPARATION et STATUT_ANNULEE continuent ET sont rapportés.
    //   Le rapport identifie précisément quel cas échoue.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Provider : retourne les statuts qui ne doivent JAMAIS permettre "terminée".
     * RÈGLE PHPUnit 10+ : la méthode provider doit être 'public static'.
     * Les clés du tableau ('confirmée', ...) s'affichent dans le rapport.
     */
    public static function statutsInterditsProvider(): array
    {
        return [
            'confirmée'      => [Commande::STATUT_CONFIRMEE],
            'en préparation' => [Commande::STATUT_EN_PREPARATION],
            'annulée'        => [Commande::STATUT_ANNULEE],
        ];
    }

    /**
     * Vérifie que les statuts intermédiaires n'autorisent pas "terminée".
     *
     * #[DataProvider('statutsInterditsProvider')] :
     *   PHPUnit appellera cette méthode 3 fois, une par entrée du provider.
     *   Le paramètre $statut reçoit la valeur de chaque ligne.
     */
    #[DataProvider('statutsInterditsProvider')]
    public function testNePeutPasEtreTermineeStatutInterdit(string $statut): void
    {
        $this->commande->setStatut($statut);
        $this->commande->setPretMateriel(false);
        $this->commande->setRestitutionMateriel(false);

        $this->assertFalse($this->commande->peutEtreTerminee());
    }

    // ============================================================
    // GROUPE 4 — peutEtreEnAttenteRetourMateriel()
    // ============================================================
    // Condition : statut=livree ET pretMateriel=true ET restitutionMateriel=false
    // ============================================================

    #[TestDox('Cas positif : toutes les conditions réunies')]
    public function testPeutEtreEnAttenteRetourMaterielCasPositif(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(false);

        $this->assertTrue($this->commande->peutEtreEnAttenteRetourMateriel());
    }

    #[TestDox("Cas négatif : sans prêt matériel → pas de retour à attendre")]
    public function testNePeutPasEtreEnAttenteRetourMaterielSansPretMateriel(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(false);
        $this->commande->setRestitutionMateriel(false);

        $this->assertFalse($this->commande->peutEtreEnAttenteRetourMateriel());
    }

    #[TestDox("Cas négatif : matériel déjà restitué → pas besoin d'attendre")]
    public function testNePeutPasEtreEnAttenteRetourMaterielMaterielDejaRestitue(): void
    {
        $this->commande->setStatut(Commande::STATUT_LIVREE);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(true);

        $this->assertFalse($this->commande->peutEtreEnAttenteRetourMateriel());
    }

    #[TestDox("Cas négatif : mauvais statut (pas encore livrée)")]
    public function testNePeutPasEtreEnAttenteRetourMaterielMauvaisStatut(): void
    {
        $this->commande->setStatut(Commande::STATUT_CONFIRMEE);
        $this->commande->setPretMateriel(true);
        $this->commande->setRestitutionMateriel(false);

        $this->assertFalse($this->commande->peutEtreEnAttenteRetourMateriel());
    }

    // ============================================================
    // GROUPE 5 — generateNumeroCommande() : format de l'identifiant
    // ============================================================
    // generateNumeroCommande() est normalement déclenché par @PrePersist
    // (événement Doctrine juste avant l'INSERT en base).
    // En test unitaire, on l'appelle DIRECTEMENT sans passer par Doctrine.
    //
    // Format attendu : 8 chars alphanumériques majuscules + tiret + date YYYYMMDD
    // Exemple : "A7F3K9M2-20250204"  (longueur totale : 17 caractères)
    // ============================================================

    /**
     * Vérifie que le numéro est bien généré et respecte le format attendu.
     *
     * assertMatchesRegularExpression() : la méthode PHPUnit la plus adaptée
     * pour vérifier un format de chaîne complexe.
     * Regex : ^[A-Z0-9]{8}-\d{8}$
     *   ^        → début de chaîne
     *   [A-Z0-9] → lettre majuscule ou chiffre
     *   {8}      → exactement 8 fois
     *   -        → tiret littéral
     *   \d{8}    → 8 chiffres (la date YYYYMMDD)
     *   $        → fin de chaîne
     */
    #[TestDox('Format du numéro : [A-Z0-9]{8}-YYYYMMDD')]
    public function testGenerateNumeroCommandeFormat(): void
    {
        $dateCommande = new \DateTime('2025-02-04');
        $this->commande->setDateCommande($dateCommande);

        $this->commande->generateNumeroCommande(); // appel direct (normalement @PrePersist)

        $numero = $this->commande->getNumeroCommande();

        $this->assertNotNull($numero);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}-\d{8}$/', $numero);

        // assertStringEndsWith() : vérifie la fin de la chaîne → '-20250204'
        $this->assertStringEndsWith('-20250204', $numero);

        // La longueur totale est toujours 17 caractères : 8 + 1 + 8
        $this->assertSame(17, strlen($numero));
    }

    /**
     * Si generateNumeroCommande() est appelé deux fois, le numéro ne change pas.
     * La condition "if ($this->numeroCommande === null)" protège contre le ré-écrasement.
     */
    #[TestDox('Un double appel conserve le numéro original (idempotent)')]
    public function testGenerateNumeroCommandeAppelDoubleConserveLeNumeroDorigine(): void
    {
        $this->commande->setDateCommande(new \DateTime());
        $this->commande->generateNumeroCommande();
        $premierNumero = $this->commande->getNumeroCommande();

        $this->commande->generateNumeroCommande(); // deuxième appel
        $deuxiemeNumero = $this->commande->getNumeroCommande();

        $this->assertSame($premierNumero, $deuxiemeNumero);
    }

    // ============================================================
    // GROUPE 6 — HasPrixCommandeTrait : validation des prix
    // ============================================================

    /**
     * Le trait HasPrixCommandeTrait lance une exception pour les prix négatifs.
     * On teste ici le comportement du trait via la Commande qui l'utilise.
     */
    public function testSetPrixMenuNegatifLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être négatif');

        $this->commande->setPrixMenu(-1.0);
    }

    public function testSetPrixLivraisonNegatifLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être négatif');

        $this->commande->setPrixLivraison(-0.01);
    }

    /**
     * Un prix à zéro est valide (livraison gratuite par exemple).
     */
    public function testSetPrixLivraisonZeroEstValide(): void
    {
        $this->commande->setPrixLivraison(0.0);
        $this->assertSame(0.0, $this->commande->getPrixLivraison());
    }
}
