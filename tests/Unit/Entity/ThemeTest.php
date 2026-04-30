<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Menu;
use App\Entity\Theme;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    private Theme $theme;

    protected function setUp(): void
    {
        $this->theme = new Theme();
    }

    public function testLibelleInitialementNull(): void
    {
        $this->assertNull($this->theme->getLibelle());
    }

    public function testSetLibelleValide(): void
    {
        $this->theme->setLibelle('Gastronomique');

        $this->assertSame('Gastronomique', $this->theme->getLibelle());
    }

    public function testSetLibelleVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->theme->setLibelle('');
    }

    public function testSetLibelleEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->theme->setLibelle('   ');
    }

    public function testMenusInitialementVides(): void
    {
        $this->assertCount(0, $this->theme->getMenus());
    }

    public function testAddMenuAjouteDansLaCollection(): void
    {
        $menu = new Menu();

        $this->theme->addMenu($menu);

        $this->assertCount(1, $this->theme->getMenus());
        $this->assertTrue($this->theme->getMenus()->contains($menu));
    }

    public function testAddMenuNeCreePasDeDoublon(): void
    {
        $menu = new Menu();

        $this->theme->addMenu($menu);
        $this->theme->addMenu($menu);

        $this->assertCount(1, $this->theme->getMenus());
    }

    public function testAddMenuSynchroniseLaRelationInverse(): void
    {
        $menu = new Menu();

        $this->theme->addMenu($menu);

        $this->assertSame($this->theme, $menu->getTheme());
    }

    public function testRemoveMenuSupprimeDeCollection(): void
    {
        $menu = new Menu();
        $this->theme->addMenu($menu);

        $this->theme->removeMenu($menu);

        $this->assertCount(0, $this->theme->getMenus());
    }

    public function testRemoveMenuDesynchroniseLaRelationInverse(): void
    {
        $menu = new Menu();
        $this->theme->addMenu($menu);

        $this->theme->removeMenu($menu);

        $this->assertNull($menu->getTheme());
    }
}
