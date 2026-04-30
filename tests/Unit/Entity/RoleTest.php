<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Role;
use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    private Role $role;

    protected function setUp(): void
    {
        $this->role = new Role();
    }

    public function testLibelleInitialementNull(): void
    {
        $this->assertNull($this->role->getLibelle());
    }

    public function testSetLibelleValide(): void
    {
        $this->role->setLibelle('ROLE_ADMIN');

        $this->assertSame('ROLE_ADMIN', $this->role->getLibelle());
    }

    public function testSetLibelleVideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->role->setLibelle('');
    }

    public function testSetLibelleEspacesSeulsLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être vide');

        $this->role->setLibelle('   ');
    }

    public function testUtilisateursInitialementVides(): void
    {
        $this->assertCount(0, $this->role->getUtilisateurs());
    }

    public function testAddUtilisateurAjouteDansLaCollection(): void
    {
        $utilisateur = new Utilisateur();

        $this->role->addUtilisateur($utilisateur);

        $this->assertCount(1, $this->role->getUtilisateurs());
        $this->assertTrue($this->role->getUtilisateurs()->contains($utilisateur));
    }

    public function testAddUtilisateurNeCreePasDeDoublon(): void
    {
        $utilisateur = new Utilisateur();

        $this->role->addUtilisateur($utilisateur);
        $this->role->addUtilisateur($utilisateur);

        $this->assertCount(1, $this->role->getUtilisateurs());
    }

    public function testAddUtilisateurSynchroniseLaRelationInverse(): void
    {
        $this->role->setLibelle('ROLE_USER');
        $utilisateur = new Utilisateur();

        $this->role->addUtilisateur($utilisateur);

        // La relation bidirectionnelle est synchronisée : utilisateur.role === ce rôle
        $this->assertSame($this->role, $utilisateur->getRole());
    }

    public function testRemoveUtilisateurSupprimeDeCollection(): void
    {
        $utilisateur = new Utilisateur();
        $this->role->addUtilisateur($utilisateur);

        $this->role->removeUtilisateur($utilisateur);

        $this->assertCount(0, $this->role->getUtilisateurs());
    }

    /**
     * removeUtilisateur() doit aussi désynchroniser la relation inverse.
     * Le code fait $utilisateur->setRole(null) lors de la suppression.
     * Sans ce test, une régression (oubli du setRole(null)) passerait inaperçue.
     */
    public function testRemoveUtilisateurDesynchroniseLaRelationInverse(): void
    {
        $this->role->setLibelle('ROLE_USER');
        $utilisateur = new Utilisateur();
        $this->role->addUtilisateur($utilisateur);

        $this->role->removeUtilisateur($utilisateur);

        $this->assertNull($utilisateur->getRole());
    }
}
