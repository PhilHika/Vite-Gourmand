<?php

namespace App\Repository;

use App\Document\Horaire;
use App\Service\QueryCacheService;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Horaire>
 */
class HoraireRepository extends ServiceDocumentRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly QueryCacheService $queryCacheService
    ) {
        parent::__construct($registry, Horaire::class);
    }

    // Les méthodes de base (find, findAll, findBy, findOneBy) sont
    // déjà fournies par ServiceDocumentRepository.
    //
    // Exemple de requête personnalisée :
    //
    //    public function findByJour(string $jour): array
    //    {
    //        return $this->createQueryBuilder()
    //            ->field('jour')->equals($jour)
    //            ->getQuery()
    //            ->execute()
    //            ->toArray();
    //    }

    /**
     * Retourne TOUS les horaires (en cache 1h)
     * Utilisé par : HorairesExtension (Twig)
     */
    public function findAllCached(int $ttl = 3600): array
    {
        return $this->queryCacheService->getOrFetch(
            'horaires_all',
            fn() => $this->findAll(),
            $ttl
        );
    }
}
