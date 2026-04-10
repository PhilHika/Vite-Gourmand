<?php

namespace App\Repository;

use App\Entity\Regime;
use App\Service\QueryCacheService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Regime>
 */
class RegimeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly QueryCacheService $queryCacheService
    ) {
        parent::__construct($registry, Regime::class);
    }

    //    /**
    //     * @return Regime[] Returns an array of Regime objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Regime
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne TOUS les régimes (en cache 24h)
     * Utilisé par : AdminReferentielController::listRegimes (GET)
     */
    public function findAllCached(int $ttl = 86400): array
    {
        return $this->queryCacheService->getOrFetch(
            'regimes_all',
            fn() => $this->findAll(),
            $ttl
        );
    }
}
