<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    //    /**
    //     * @return Menu[] Returns an array of Menu objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Menu
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * @return Menu[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('m');

        if (!empty($filters['prixMin'])) {
            $qb->andWhere('m.prixParPersonne >= :prixMin')
               ->setParameter('prixMin', $filters['prixMin']);
        }

        if (!empty($filters['prixMax'])) {
            $qb->andWhere('m.prixParPersonne <= :prixMax')
               ->setParameter('prixMax', $filters['prixMax']);
        }

        if (!empty($filters['theme'])) {
            $qb->andWhere('m.theme = :theme')
               ->setParameter('theme', $filters['theme']);
        }

        if (!empty($filters['regime'])) {
            $qb->andWhere('m.regime = :regime')
               ->setParameter('regime', $filters['regime']);
        }

        if (!empty($filters['nombrePersonne'])) {
            $qb->andWhere('m.nombrePersonneMinimum <= :nombrePersonne')
               ->setParameter('nombrePersonne', $filters['nombrePersonne']);
        }

        return $qb->orderBy('m.titre', 'ASC')
                   ->getQuery()
                   ->getResult();
    }

    public function save(Menu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Menu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
