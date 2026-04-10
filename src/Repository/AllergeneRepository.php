<?php

namespace App\Repository;

use App\Entity\Allergene;
use App\Service\QueryCacheService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Allergene>
 */
class AllergeneRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly QueryCacheService $queryCacheService
    ) {
        parent::__construct($registry, Allergene::class);
    }

    //    /**
    //     * @return Allergene[] Returns an array of Allergene objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Allergene
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne TOUS les allergènes (en cache 24h)
     * Utilisé par : AdminReferentielController::listAllergenes (GET)
     */
    public function findAllCached(int $ttl = 86400): array
    {
        return $this->queryCacheService->getOrFetch(
            'allergenes_all',
            fn() => $this->findAll(),
            $ttl
        );
    }
}
