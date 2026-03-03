<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    /**
     * Recherche un token valide (existant et non expiré).
     */
    public function findValidToken(string $token): ?ResetPasswordRequest
    {
        return $this->createQueryBuilder('resetReq')
            ->where('resetReq.token = :token')
            ->andWhere('resetReq.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime tous les tokens existants pour un utilisateur donné.
     */
    public function removeTokensForUser(Utilisateur $user): void
    {
        $this->createQueryBuilder('resetReq')
            ->delete()
            ->where('resetReq.utilisateur = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime tous les tokens expirés (maintenance).
     */
    public function removeExpiredTokens(): void
    {
        $this->createQueryBuilder('resetReq')
            ->delete()
            ->where('resetReq.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
