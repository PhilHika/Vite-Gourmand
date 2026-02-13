<?php

namespace App\Repository;

use App\Document\Horaire;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<Horaire>
 */
class HoraireRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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
}
