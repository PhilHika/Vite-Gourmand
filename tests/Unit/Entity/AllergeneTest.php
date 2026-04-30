<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Allergene;
use App\Entity\Plat;
use PHPUnit\Framework\TestCase;

class AllergeneTest extends TestCase
{
    private Allergene $allergene;

    protected function setUp(): void
    {
        $this->allergene = new Allergene();
    }

    public function testLibelleInitialementNull(): void
    {
        $this->assertNull($this->allergene->getLibelle());
    }

    public function testSetLibelleValide(): void
    {
        $this->allergene->setLibelle('Gluten');

        $this->assertSame('Gluten', $this->allergene->getLibelle());
    }

    public function testSetLibelleVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->allergene->setLibelle('');
    }

    public function testSetLibelleEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->allergene->setLibelle('   ');
    }

    // Allergene::$plats n'a pas de getter public — la relation se vérifie depuis Plat
    public function testAddAllergeneSynchroniseLaCollectionDuPlatEtDeLAllergene(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Saumon');

        // Plat::addAllergene() appelle allergene->referencePlat() en interne
        $plat->addAllergene($this->allergene);

        $this->assertCount(1, $plat->getAllergenes());
        $this->assertTrue($plat->getAllergenes()->contains($this->allergene));
    }

    public function testRemoveAllergeneDesynchroniseLesDeux(): void
    {
        $plat = new Plat();
        $plat->setTitrePlat('Saumon');
        $plat->addAllergene($this->allergene);

        $plat->removeAllergene($this->allergene);

        $this->assertCount(0, $plat->getAllergenes());
    }
}
