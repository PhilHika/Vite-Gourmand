<?php

namespace App\Repository;

use App\Document\ContenuSite;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;

/**
 * @extends ServiceDocumentRepository<ContenuSite>
 */
class ContenuSiteRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContenuSite::class);
    }

    public function findByCle(string $cle): ?ContenuSite
    {
        return $this->findOneBy(['cle' => $cle]);
    }
}
