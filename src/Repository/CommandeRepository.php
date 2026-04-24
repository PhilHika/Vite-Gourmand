<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * @return Commande[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.dateCommande', 'DESC');

        if (!empty($filters['statut'])) {
            $qb->andWhere('c.statut = :statut')
               ->setParameter('statut', $filters['statut']);
        }

        if (!empty($filters['utilisateur'])) {
            $qb->andWhere('c.utilisateur = :utilisateur')
               ->setParameter('utilisateur', $filters['utilisateur']);
        }

        return $qb->getQuery()->getResult();
    }
}
