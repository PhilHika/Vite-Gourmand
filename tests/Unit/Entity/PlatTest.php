<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — PlatTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester l'entité Plat : validation du titre, logique de photo
 * (getPhotoPath), et gestion des collections (allergènes).
 *
 * TYPE DE TEST : Unitaire (Unit Test)
 * → Pas de BDD. On crée les objets directement avec "new".
 *
 * CONCEPTS PHPUNIT ILLUSTRÉS :
 *   - assertSame            → comparaison stricte (valeur + type)
 *   - assertCount()         → vérifier la taille d'une collection
 *   - assertTrue()          → vérifier une condition booléenne
 *   - expectException()     → capter une exception
 *   - Tester les collections Doctrine (ArrayCollection)
 *
 * STRUCTURE :
 *   Groupe 1 : getPhotoPath() — logique d'affichage de l'image
 *   Groupe 2 : setTitrePlat() — validation d'entrée
 *   Groupe 3 : Collections    — ajout/suppression d'allergènes
 * ============================================================
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Allergene;
use App\Entity\Plat;
use PHPUnit\Framework\TestCase;

class PlatTest extends TestCase
{
    private Plat $plat;

    protected function setUp(): void
    {
        $this->plat = new Plat();
    }

    // ============================================================
    // GROUPE 1 — getPhotoPath() : logique d'affichage de l'image
    // ============================================================
    // getPhotoPath() est une méthode métier simple mais critique pour
    // l'affichage en front. Elle centralise la logique du chemin d'image
    // et de l'image par défaut.
    //
    // Code source :
    //   return $this->photo
    //       ? 'uploads/plats/' . $this->photo
    //       : 'images/default_plat.jpg';
    //
    // Deux comportements à tester :
    //   → Avec photo   : retourne le chemin complet
    //   → Sans photo   : retourne l'image par défaut
    // ============================================================

    /**
     * Cas nominal : le plat a une photo → retourne le chemin uploads/plats/.
     */
    public function testGetPhotoPathAvecPhoto(): void
    {
        $nomFichier = 'saumon-gravlax.jpg';
        $this->plat->setPhoto($nomFichier);

        $cheminAttendu = 'uploads/plats/saumon-gravlax.jpg';

        // assertSame() : strict → vérifie le type ET la valeur
        $this->assertSame($cheminAttendu, $this->plat->getPhotoPath());
    }

    /**
     * Cas sans photo (null par défaut) → retourne l'image par défaut.
     * C'est le comportement "fallback" : si aucune photo n'est uploadée,
     * le front affiche toujours une image plutôt qu'une image cassée.
     */
    public function testGetPhotoPathSansPhoto(): void
    {
        // $photo est null par défaut (pas de setPhoto() appelé)
        $this->assertSame('images/default_plat.jpg', $this->plat->getPhotoPath());
    }

    /**
     * Cas explicite : setPhoto(null) → même résultat que sans photo.
     * Utile quand on efface délibérément une photo existante.
     */
    public function testGetPhotoPathApresRemiseANull(): void
    {
        $this->plat->setPhoto('ancienne-photo.jpg');
        $this->plat->setPhoto(null); // on efface la photo

        $this->assertSame('images/default_plat.jpg', $this->plat->getPhotoPath());
    }

    // ============================================================
    // GROUPE 2 — setTitrePlat() : validation d'entrée
    // ============================================================

    /**
     * Cas nominal : un titre valide est accepté et stocké.
     */
    public function testSetTitrePlatValide(): void
    {
        $this->plat->setTitrePlat('Saumon gravlax');

        $this->assertSame('Saumon gravlax', $this->plat->getTitrePlat());
    }

    /**
     * setTitrePlat('') → InvalidArgumentException.
     * La méthode fait : if (empty(trim($titrePlat))) throw new \InvalidArgumentException(...)
     */
    public function testSetTitrePlatVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->plat->setTitrePlat('');
    }

    /**
     * Titre composé uniquement d'espaces → traité comme vide (trim).
     * C'est un cas limite important à ne pas oublier.
     */
    public function testSetTitrePlatEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->plat->setTitrePlat('     ');
    }

    // ============================================================
    // GROUPE 3 — Collections : gestion des allergènes
    // ============================================================
    // Doctrine utilise ArrayCollection pour les relations ManyToMany.
    // Le constructeur de Plat initialise : $this->allergenes = new ArrayCollection();
    //
    // On teste :
    //   - La collection est vide à l'instanciation
    //   - addAllergene() ajoute bien l'allergène
    //   - addAllergene() est idempotent (pas de doublon)
    //   - removeAllergene() supprime bien l'allergène
    //   - La relation bidirectionnelle est gérée (Allergene.referencePlat())
    // ============================================================

    /**
     * À l'instanciation, un Plat n'a aucun allergène.
     *
     * assertCount($expected, $haystack) :
     *   - $expected : nombre d'éléments attendus
     *   - $haystack : le tableau ou la collection à mesurer
     */
    public function testAllergeneInitialementVide(): void
    {
        $this->assertCount(0, $this->plat->getAllergenes());
    }

    /**
     * Ajout d'un allergène : la collection doit contenir 1 élément.
     */
    public function testAddAllergeneAjouteLAllergene(): void
    {
        $allergene = new Allergene();
        $allergene->setLibelle('Gluten');

        $this->plat->addAllergene($allergene);

        // La collection contient maintenant 1 allergène
        $this->assertCount(1, $this->plat->getAllergenes());

        // assertTrue + contains() : vérifie que cet allergène spécifique est présent
        $this->assertTrue($this->plat->getAllergenes()->contains($allergene));
    }

    /**
     * addAllergene() est idempotent : ajouter deux fois le même allergène
     * ne doit PAS créer de doublon (la méthode vérifie avec contains()).
     *
     * Code source :
     *   if (!$this->allergenes->contains($allergene)) {
     *       $this->allergenes->add($allergene);
     *   }
     */
    public function testAddAllergeneNeCreePasDeDoublon(): void
    {
        $allergene = new Allergene();
        $allergene->setLibelle('Lactose');

        $this->plat->addAllergene($allergene);
        $this->plat->addAllergene($allergene); // deuxième ajout du même objet

        // Toujours 1 seul allergène, pas 2
        $this->assertCount(1, $this->plat->getAllergenes());
    }

    /**
     * removeAllergene() supprime bien l'allergène de la collection.
     */
    public function testRemoveAllergeneSupprimeAllergene(): void
    {
        $allergene = new Allergene();
        $allergene->setLibelle('Œufs');

        $this->plat->addAllergene($allergene);
        $this->assertCount(1, $this->plat->getAllergenes()); // vérification intermédiaire

        $this->plat->removeAllergene($allergene);

        // La collection est de nouveau vide
        $this->assertCount(0, $this->plat->getAllergenes());
    }

    /**
     * Ajout de plusieurs allergènes distincts.
     * Vérifie que la collection gère bien plusieurs éléments.
     */
    public function testAddPlusieursAllergenes(): void
    {
        $gluten = (new Allergene())->setLibelle('Gluten');
        $lactose = (new Allergene())->setLibelle('Lactose');
        $fruits = (new Allergene())->setLibelle('Fruits à coque');

        $this->plat->addAllergene($gluten);
        $this->plat->addAllergene($lactose);
        $this->plat->addAllergene($fruits);

        $this->assertCount(3, $this->plat->getAllergenes());
    }
}
