<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — ContenuSiteExtensionTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester ContenuSiteExtension : getDescriptionSite() et getConditionsVente().
 * Ces méthodes utilisent l'opérateur null-safe (?->) : si MongoDB ne retourne
 * aucun document pour la clé cherchée, elles doivent retourner null sans planter.
 *
 * POURQUOI TESTER L'OPÉRATEUR NULL-SAFE :
 *   $doc?->getContenu() retourne null si $doc est null.
 *   Sans ce test, une régression (suppression du "?") provoquerait une
 *   TypeError en prod si la clé MongoDB est absente — silencieux en dev
 *   si le document est toujours présent.
 *
 * RÈGLE createMock() vs createStub() rappelée ici :
 *   createMock()  → quand on vérifie comment la méthode est appelée (expects + with)
 *   createStub()  → quand on veut juste contrôler la valeur de retour (willReturn seul)
 *
 * STRUCTURE :
 *   Groupe 1 : getDescriptionSite() — document trouvé / absent / contenu null
 *   Groupe 2 : getConditionsVente() — mêmes cas
 *   Groupe 3 : getFunctions() — enregistrement des fonctions Twig
 * ============================================================
 */

namespace App\Tests\Unit\Twig;

use App\Document\ContenuSite;
use App\Repository\ContenuSiteRepository;
use App\Twig\ContenuSiteExtension;
use PHPUnit\Framework\TestCase;

class ContenuSiteExtensionTest extends TestCase
{
    private function creerExtension(ContenuSiteRepository $repository): ContenuSiteExtension
    {
        return new ContenuSiteExtension($repository);
    }

    private function creerDocument(string $cle, ?string $contenu): ContenuSite
    {
        $doc = new ContenuSite();
        $doc->setCle($cle);
        $doc->setContenu($contenu);

        return $doc;
    }

    // ============================================================
    // GROUPE 1 — getDescriptionSite()
    // ============================================================

    /**
     * Document 'description' trouvé → retourne son contenu.
     *
     * createMock() + expects($this->once()) + with() :
     *   On vérifie aussi QUE findByCle() est appelée avec la bonne clé 'description'.
     *   Si quelqu'un change 'description' en 'desc' dans le service → ce test échoue.
     */
    public function testGetDescriptionSiteDocumentTrouveRetourneLeContenu(): void
    {
        $document = $this->creerDocument('description', 'Bienvenue chez Vite & Gourmand.');

        $repositoryMock = $this->createMock(ContenuSiteRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findByCle')
            ->with('description')
            ->willReturn($document);

        $extension = $this->creerExtension($repositoryMock);

        $this->assertSame('Bienvenue chez Vite & Gourmand.', $extension->getDescriptionSite());
    }

    /**
     * Document absent (MongoDB vide ou clé inexistante) → null sans exception.
     * C'est le cas protégé par l'opérateur ?->.
     *
     * createStub() : on ne vérifie pas l'argument, juste la valeur de retour null.
     */
    public function testGetDescriptionSiteDocumentAbsentRetourneNull(): void
    {
        $stub = $this->createStub(ContenuSiteRepository::class);
        $stub->method('findByCle')->willReturn(null);

        $extension = $this->creerExtension($stub);

        $this->assertNull($extension->getDescriptionSite());
    }

    /**
     * Document présent mais contenu null → retourne null (contenu non encore saisi).
     */
    public function testGetDescriptionSiteContenuNullRetourneNull(): void
    {
        $document = $this->creerDocument('description', null);

        $stub = $this->createStub(ContenuSiteRepository::class);
        $stub->method('findByCle')->willReturn($document);

        $extension = $this->creerExtension($stub);

        $this->assertNull($extension->getDescriptionSite());
    }

    // ============================================================
    // GROUPE 2 — getConditionsVente()
    // ============================================================

    /**
     * Document 'conditions_vente' trouvé → retourne le contenu.
     */
    public function testGetConditionsVenteDocumentTrouveRetourneLeContenu(): void
    {
        $document = $this->creerDocument('conditions_vente', '<p>CGV de Vite & Gourmand.</p>');

        $repositoryMock = $this->createMock(ContenuSiteRepository::class);
        $repositoryMock->expects($this->once())
            ->method('findByCle')
            ->with('conditions_vente')
            ->willReturn($document);

        $extension = $this->creerExtension($repositoryMock);

        $this->assertSame('<p>CGV de Vite & Gourmand.</p>', $extension->getConditionsVente());
    }

    /**
     * Document 'conditions_vente' absent → null sans exception.
     */
    public function testGetConditionsVenteDocumentAbsentRetourneNull(): void
    {
        $stub = $this->createStub(ContenuSiteRepository::class);
        $stub->method('findByCle')->willReturn(null);

        $extension = $this->creerExtension($stub);

        $this->assertNull($extension->getConditionsVente());
    }

    // ============================================================
    // GROUPE 3 — getFunctions() : enregistrement Twig
    // ============================================================

    /**
     * Vérifie que les deux fonctions Twig sont bien déclarées.
     */
    public function testGetFunctionsEnregistreLesDeuFonctions(): void
    {
        $stub = $this->createStub(ContenuSiteRepository::class);
        $extension = $this->creerExtension($stub);

        $fonctions = $extension->getFunctions();

        $this->assertCount(2, $fonctions);

        $noms = array_map(fn($f) => $f->getName(), $fonctions);

        $this->assertContains('get_description_site', $noms);
        $this->assertContains('get_conditions_vente', $noms);
    }
}
