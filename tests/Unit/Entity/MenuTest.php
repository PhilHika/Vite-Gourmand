<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — MenuTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester l'entité Menu, qui est centrale dans le projet :
 *   - Utilisée par Commande::calculerPrixMenu()
 *   - Possède DEUX collections via traits :
 *       * plats (HasPlatsTrait, ManyToMany avec Plat)
 *       * commandes (HasCommandesMenuTrait, OneToMany avec Commande)
 *
 * POURQUOI TESTER MENU DIRECTEMENT :
 *   Aujourd'hui Menu est testé indirectement via CommandeTest.
 *   Un bug dans addPlat() (ex : oubli du sync inverse $plat->addMenu($this))
 *   passerait inaperçu dans CommandeTest qui ne touche pas aux plats.
 *
 * STRUCTURE :
 *   Groupe 1 : Setters/Getters basiques
 *   Groupe 2 : Collection plats (ManyToMany) — addPlat / removePlat avec sync
 *   Groupe 3 : Collection commandes (OneToMany) — addCommande / removeCommande
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Plat;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    private Menu $menu;

    protected function setUp(): void
    {
        $this->menu = new Menu();
    }

    // ============================================================
    // GROUPE 1 — Setters/Getters basiques
    // ============================================================

    public function testTitreInitialementNull(): void
    {
        $this->assertNull($this->menu->getTitre());
    }

    public function testSetTitreStockeLaValeur(): void
    {
        $this->menu->setTitre('Menu de Noël');

        $this->assertSame('Menu de Noël', $this->menu->getTitre());
    }

    public function testSetPrixParPersonneStockeLaValeur(): void
    {
        $this->menu->setPrixParPersonne(45.50);

        $this->assertSame(45.50, $this->menu->getPrixParPersonne());
    }

    public function testSetNombrePersonneMinimumStockeLaValeur(): void
    {
        $this->menu->setNombrePersonneMinimum(10);

        $this->assertSame(10, $this->menu->getNombrePersonneMinimum());
    }

    public function testSetQuantiteRestanteStockeLaValeur(): void
    {
        $this->menu->setQuantiteRestante(25);

        $this->assertSame(25, $this->menu->getQuantiteRestante());
    }

    // ============================================================
    // GROUPE 2 — Collection plats (ManyToMany)
    // ============================================================
    // HasPlatsTrait::addPlat() :
    //   - Si le plat n'est PAS déjà dans la collection → l'ajoute
    //     ET appelle $plat->addMenu($this) (sync inverse)
    //   - Si déjà présent → ne fait rien (pas de doublon)
    //
    // HasPlatsTrait::removePlat() :
    //   - Si le plat est présent → le retire ET appelle $plat->removeMenu($this)
    // ============================================================

    public function testPlatsInitialementVides(): void
    {
        $this->assertCount(0, $this->menu->getPlats());
    }

    public function testAddPlatAjouteDansLaCollection(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Foie gras maison');

        $this->menu->addPlat($plat);

        $this->assertCount(1, $this->menu->getPlats());
        $this->assertTrue($this->menu->getPlats()->contains($plat));
    }

    /**
     * La relation est ManyToMany bidirectionnelle :
     *   $menu->addPlat($plat) doit aussi ajouter $menu dans $plat->getMenus().
     * Sans ce sync, Doctrine ne persistera pas la relation correctement.
     */
    public function testAddPlatSynchroniseLaRelationInverse(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Tartare de saumon');

        $this->menu->addPlat($plat);

        // Le plat doit aussi référencer le menu (côté inverse de la relation)
        $this->assertTrue($plat->getMenus()->contains($this->menu));
    }

    public function testAddPlatNeCreePasDeDoublon(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Risotto');

        $this->menu->addPlat($plat);
        $this->menu->addPlat($plat); // 2ᵉ ajout du même plat

        $this->assertCount(1, $this->menu->getPlats());
    }

    public function testRemovePlatSupprimeDeLaCollection(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Tarte au citron');

        $this->menu->addPlat($plat);
        $this->menu->removePlat($plat);

        $this->assertCount(0, $this->menu->getPlats());
    }

    /**
     * Sync inverse au remove : retirer un plat doit aussi retirer le menu
     * de la collection inverse $plat->getMenus().
     */
    public function testRemovePlatDesynchroniseLaRelationInverse(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Mousse au chocolat');

        $this->menu->addPlat($plat);
        $this->menu->removePlat($plat);

        $this->assertFalse($plat->getMenus()->contains($this->menu));
    }

    /**
     * Plusieurs plats : la collection accepte N éléments distincts.
     */
    public function testAddPlusieursPlats(): void
    {
        $plat1 = new Plat();
        $plat1->setTitrePlat('Entrée');
        $plat2 = new Plat();
        $plat2->setTitrePlat('Plat principal');
        $plat3 = new Plat();
        $plat3->setTitrePlat('Dessert');

        $this->menu->addPlat($plat1);
        $this->menu->addPlat($plat2);
        $this->menu->addPlat($plat3);

        $this->assertCount(3, $this->menu->getPlats());
    }

    // ============================================================
    // GROUPE 3 — Collection commandes (OneToMany)
    // ============================================================
    // HasCommandesMenuTrait::addCommande() :
    //   - Ajoute la commande à la collection
    //   - Définit $commande->setMenu($this) (côté propriétaire de la relation)
    //
    // HasCommandesMenuTrait::removeCommande() :
    //   - Retire la commande
    //   - Si $commande->getMenu() === $this → setMenu(null) (sync)
    // ============================================================

    public function testCommandesInitialementVides(): void
    {
        $this->assertCount(0, $this->menu->getCommandes());
    }

    public function testAddCommandeAjouteEtSynchroniseLaRelationInverse(): void
    {
        $commande = new Commande();

        $this->menu->addCommande($commande);

        $this->assertCount(1, $this->menu->getCommandes());
        // Sync inverse : la commande référence le menu
        $this->assertSame($this->menu, $commande->getMenu());
    }

    public function testAddCommandeNeCreePasDeDoublon(): void
    {
        $commande = new Commande();

        $this->menu->addCommande($commande);
        $this->menu->addCommande($commande);

        $this->assertCount(1, $this->menu->getCommandes());
    }

    public function testRemoveCommandeSupprimeEtDesynchronise(): void
    {
        $commande = new Commande();

        $this->menu->addCommande($commande);
        $this->menu->removeCommande($commande);

        $this->assertCount(0, $this->menu->getCommandes());
        // Sync inverse au remove : la commande ne référence plus le menu
        $this->assertNull($commande->getMenu());
    }
}
