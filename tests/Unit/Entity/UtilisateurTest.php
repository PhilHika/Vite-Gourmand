<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — UtilisateurTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester la logique métier de l'entité Utilisateur, notamment :
 *   1. getRoles()       → implémentation NON-STANDARD de UserInterface
 *   2. setTelephone()   → nettoyage automatique des caractères non-numériques
 *   3. getUserIdentifier() → contrat de l'interface Symfony
 *
 * POURQUOI CES MÉTHODES SONT IMPORTANTES :
 *   - getRoles() : c'est le point d'entrée du système de sécurité Symfony.
 *     Si elle retourne mal le rôle, TOUTE la sécurité est cassée.
 *   - setTelephone() : transforme l'input utilisateur (preg_replace).
 *     Un bug ici = des données téléphone corrompues en base.
 *
 * NOUVEAUX CONCEPTS PHPUNIT :
 *   - Tester une interface (UserInterface) → vérifier le contrat
 *   - assertContains()   → vérifier qu'un tableau contient une valeur
 *   - assertInstanceOf() → vérifier le type d'un objet
 *   - assertNotEmpty()   → vérifier qu'un tableau/string n'est pas vide
 *
 * ARCHITECTURE PARTICULIÈRE :
 *   Utilisateur.getRoles() est NON-STANDARD Symfony.
 *   Pattern classique : $roles = ['ROLE_USER', 'ROLE_ADMIN']  (tableau simple)
 *   Pattern de ce projet : rôle stocké dans une entité Role liée en BDD
 *   → getRoles() délègue à $this->role?->getLibelle() ?? 'ROLE_USER'
 *
 * STRUCTURE :
 *   Groupe 1 : getRoles() — contrat de sécurité
 *   Groupe 2 : getUserIdentifier() — contrat UserInterface
 *   Groupe 3 : setTelephone() — nettoyage de données
 *   Groupe 4 : Collections    — initialisation
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Role;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\User\UserInterface;
use PHPUnit\Framework\TestCase;

class UtilisateurTest extends TestCase
{
    private Utilisateur $utilisateur;

    protected function setUp(): void
    {
        $this->utilisateur = new Utilisateur();
    }

    // ============================================================
    // GROUPE 1 — getRoles() : contrat de sécurité Symfony
    // ============================================================
    // Symfony appelle getRoles() pour décider des droits d'accès.
    // La méthode doit TOUJOURS retourner un tableau non-vide.
    //
    // Code source :
    //   return array_unique([
    //       $this->role?->getLibelle() ?? 'ROLE_USER'
    //   ]);
    //
    // Deux cas à tester :
    //   1. Avec un rôle défini    → retourne [libelle du rôle]
    //   2. Sans rôle (null)       → retourne ['ROLE_USER'] (fallback de sécurité)
    // ============================================================

    /**
     * Cas nominal : un rôle est défini → getRoles() retourne le libellé de ce rôle.
     *
     * assertContains($needle, $haystack) :
     *   Vérifie que $needle EST PRÉSENT dans $haystack (tableau ou itérable).
     *   Préférable à assertEquals([...]) car l'ordre du tableau n'a pas d'importance.
     */
    public function testGetRolesAvecRoleDefini(): void
    {
        $role = new Role();
        $role->setLibelle('ROLE_ADMIN');

        $this->utilisateur->setRole($role);

        $roles = $this->utilisateur->getRoles();

        // Le tableau retourné contient bien 'ROLE_ADMIN'
        $this->assertContains('ROLE_ADMIN', $roles);

        // Le contrat UserInterface : toujours au moins un rôle
        $this->assertNotEmpty($roles);
    }

    /**
     * Cas sans rôle : si $this->role est null, le fallback 'ROLE_USER' s'applique.
     *
     * C'est un cas de sécurité critique : un utilisateur sans rôle explicite
     * doit avoir le rôle minimum. Si ce fallback disparaît, un user sans rôle
     * pourrait accéder à n'importe quelle route protégée par ROLE_USER.
     */
    public function testGetRolesSansRoleRetourneRoleUserParDefaut(): void
    {
        // Pas de setRole() → $this->role est null
        $roles = $this->utilisateur->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertNotEmpty($roles);
    }

    /**
     * ROLE_USER défini explicitement → retourne ['ROLE_USER'] (pas de doublon).
     * array_unique() garantit l'absence de doublons même si 'ROLE_USER' était
     * ajouté deux fois (ici ce n'est pas le cas, mais on vérifie le contrat).
     */
    public function testGetRolesAvecRoleUserExplicite(): void
    {
        $role = new Role();
        $role->setLibelle('ROLE_USER');
        $this->utilisateur->setRole($role);

        $roles = $this->utilisateur->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        // Pas de doublon : exactement 1 entrée
        $this->assertCount(1, $roles);
    }

    /**
     * Vérifie que Utilisateur implémente bien UserInterface.
     *
     * assertInstanceOf($expected, $actual) :
     *   Vérifie que $actual est une instance de $expected (classe ou interface).
     *   Ici on vérifie le CONTRAT : Utilisateur doit être utilisable partout
     *   où Symfony attend un UserInterface.
     */
    public function testUtilisateurImplementeUserInterface(): void
    {
        $this->assertInstanceOf(UserInterface::class, $this->utilisateur);
    }

    // ============================================================
    // GROUPE 2 — getUserIdentifier() : contrat UserInterface
    // ============================================================
    // getUserIdentifier() est requis par UserInterface.
    // Symfony l'utilise pour identifier l'utilisateur en session.
    // Dans ce projet, l'identifiant est l'email.
    // ============================================================

    /**
     * getUserIdentifier() retourne l'email de l'utilisateur.
     */
    public function testGetUserIdentifierRetourneLEmail(): void
    {
        $this->utilisateur->setEmail('jean.dupont@example.com');

        $this->assertSame('jean.dupont@example.com', $this->utilisateur->getUserIdentifier());
    }

    // ============================================================
    // GROUPE 3 — setTelephone() : nettoyage de données
    // ============================================================
    // Code source :
    //   $this->telephone = preg_replace('/\D/', '', $telephone);
    //   // /\D/ = tout caractère NON-numérique → supprimé
    //
    // Cette méthode transforme l'input AVANT de le stocker.
    // Tests importants : vérifier que la transformation est correcte
    // pour différents formats d'entrée utilisateur.
    // ============================================================

    /**
     * Numéro propre sans formatage → stocké tel quel.
     */
    public function testSetTelephoneNumeroPropre(): void
    {
        $this->utilisateur->setTelephone('0612345678');

        $this->assertSame('0612345678', $this->utilisateur->getTelephone());
    }

    /**
     * Numéro avec espaces (format français courant) → espaces supprimés.
     * Entrée  : "06 12 34 56 78"
     * Stocké  : "0612345678"
     */
    public function testSetTelephoneAvecEspaces(): void
    {
        $this->utilisateur->setTelephone('06 12 34 56 78');

        $this->assertSame('0612345678', $this->utilisateur->getTelephone());
    }

    /**
     * Numéro avec tirets → tirets supprimés.
     * Entrée  : "06-12-34-56-78"
     * Stocké  : "0612345678"
     */
    public function testSetTelephoneAvecTirets(): void
    {
        $this->utilisateur->setTelephone('06-12-34-56-78');

        $this->assertSame('0612345678', $this->utilisateur->getTelephone());
    }

    /**
     * Numéro international avec "+" → "+" supprimé car non-numérique.
     * Entrée  : "+33 6 12 34 56 78"
     * Stocké  : "33612345678"
     */
    public function testSetTelephoneFormatInternational(): void
    {
        $this->utilisateur->setTelephone('+33 6 12 34 56 78');

        $this->assertSame('33612345678', $this->utilisateur->getTelephone());
    }

    /**
     * Numéro avec points → points supprimés.
     * Entrée  : "06.12.34.56.78"
     * Stocké  : "0612345678"
     */
    public function testSetTelephoneAvecPoints(): void
    {
        $this->utilisateur->setTelephone('06.12.34.56.78');

        $this->assertSame('0612345678', $this->utilisateur->getTelephone());
    }

    // ============================================================
    // GROUPE 4 — Collections : initialisation
    // ============================================================

    /**
     * À l'instanciation, l'utilisateur n'a aucun avis ni commande.
     * Les collections sont initialisées avec ArrayCollection vide dans __construct().
     */
    public function testCollectionsInitialementVides(): void
    {
        $this->assertCount(0, $this->utilisateur->getCommandes());
        $this->assertCount(0, $this->utilisateur->getAvis());
    }
}
