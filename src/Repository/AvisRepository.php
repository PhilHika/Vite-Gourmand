<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Service\QueryCacheService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly QueryCacheService $queryCacheService
    ) {
        parent::__construct($registry, Avis::class);
    }

    //    /**
    //     * @return Avis[] Returns an array of Avis objects
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

    //    public function findOneBySomeField($value): ?Avis
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne les avis PUBLIÉS (en cache)
     * Utilisé par : HomeController::index
     */
    public function findPubliesCached(int $ttl = 3600): array
    {
        return $this->queryCacheService->getOrFetch(
            'avis_publis',
            fn() => $this->findBy(['statut' => Avis::STATUT_PUBLIE], ['id' => 'DESC']),
            $ttl
        );
    }
}
