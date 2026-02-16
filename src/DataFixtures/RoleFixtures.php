<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    // Constantes réutilisables dans d'autres fixtures
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_SALARIE = 'ROLE_SALARIE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    // Références pour lier des fixtures entre elles
    public const ROLE_USER_REFERENCE = 'role-user';
    public const ROLE_SALARIE_REFERENCE = 'role-salarie';
    public const ROLE_ADMIN_REFERENCE = 'role-admin';

    public function load(ObjectManager $manager): void
    {
        $roles = [
            self::ROLE_USER => self::ROLE_USER_REFERENCE,
            self::ROLE_SALARIE => self::ROLE_SALARIE_REFERENCE,
            self::ROLE_ADMIN => self::ROLE_ADMIN_REFERENCE,
        ];

        foreach ($roles as $libelle => $reference) {
            // Vérifier si le rôle existe déjà en base
            $existingRole = $manager->getRepository(Role::class)
                ->findOneBy(['libelle' => $libelle]);

            if ($existingRole) {
                // Réutiliser le rôle existant comme référence
                $this->addReference($reference, $existingRole);
                continue;
            }

            $role = new Role();
            $role->setLibelle($libelle);
            $manager->persist($role);

            // Stocker la référence pour d'autres fixtures (ex: UtilisateurFixtures)
            $this->addReference($reference, $role);
        }

        $manager->flush();
    }
}
