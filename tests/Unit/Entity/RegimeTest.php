<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Menu;
use App\Entity\Regime;
use PHPUnit\Framework\TestCase;

class RegimeTest extends TestCase
{
    private Regime $regime;

    protected function setUp(): void
    {
        $this->regime = new Regime();
    }

    public function testLibelleInitialementNull(): void
    {
        $this->assertNull($this->regime->getLibelle());
    }

    public function testSetLibelleValide(): void
    {
        $this->regime->setLibelle('Végétarien');

        $this->assertSame('Végétarien', $this->regime->getLibelle());
    }

    public function testSetLibelleVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->regime->setLibelle('');
    }

    public function testSetLibelleEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->regime->setLibelle('   ');
    }

    public function testMenusInitialementVides(): void
    {
        $this->assertCount(0, $this->regime->getMenus());
    }

    public function testAddMenuAjouteDansLaCollection(): void
    {
        $menu = new Menu();

        $this->regime->addMenu($menu);

        $this->assertCount(1, $this->regime->getMenus());
        $this->assertTrue($this->regime->getMenus()->contains($menu));
    }

    public function testAddMenuNeCreePasDeDoublon(): void
    {
        $menu = new Menu();

        $this->regime->addMenu($menu);
        $this->regime->addMenu($menu);

        $this->assertCount(1, $this->regime->getMenus());
    }

    public function testAddMenuSynchroniseLaRelationInverse(): void
    {
        $menu = new Menu();

        $this->regime->addMenu($menu);

        $this->assertSame($this->regime, $menu->getRegime());
    }

    public function testRemoveMenuSupprimeDeCollection(): void
    {
        $menu = new Menu();
        $this->regime->addMenu($menu);

        $this->regime->removeMenu($menu);

        $this->assertCount(0, $this->regime->getMenus());
    }

    public function testRemoveMenuDesynchoniseLaRelationInverse(): void
    {
        $menu = new Menu();
        $this->regime->addMenu($menu);

        $this->regime->removeMenu($menu);

        $this->assertNull($menu->getRegime());
    }
}
