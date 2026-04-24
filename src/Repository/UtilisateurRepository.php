<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Bonne pratique : utiliser PasswordUpgraderInterface
     * premettre si les pratiques de hachage évoluent (symfony standards) d'updater les mdp.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        /** @var Utilisateur $user */
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne tous les utilisateurs ayant le rôle ROLE_SALARIE ou ROLE_ADMIN.
     *
     * @return Utilisateur[]
     */
    public function findGestionnaires(): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.role', 'r')
            ->where('r.libelle IN (:roles)')
            ->setParameter('roles', ['ROLE_SALARIE', 'ROLE_ADMIN'])
            ->getQuery()
            ->getResult();
    }
}
