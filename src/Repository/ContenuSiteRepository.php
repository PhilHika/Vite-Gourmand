<?php

namespace App\Repository;

use App\Document\ContenuSite;
use App\Service\QueryCacheService;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<ContenuSite>
 */
class ContenuSiteRepository extends ServiceDocumentRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly QueryCacheService $queryCacheService
    ) {
        parent::__construct($registry, ContenuSite::class);
    }

    public function findByCle(string $cle): ?ContenuSite
    {
        return $this->findOneBy(['cle' => $cle]);
    }

    /**
     * Retourne un contenu par clé (en cache 1h)
     * Utilisé par : ContenuExtension (Twig)
     */
    public function findByCleCached(string $cle, int $ttl = 3600): ?ContenuSite
    {
        return $this->queryCacheService->getOrFetch(
            "contenu_{$cle}",
            fn() => $this->findByCle($cle),
            $ttl
        );
    }
}
