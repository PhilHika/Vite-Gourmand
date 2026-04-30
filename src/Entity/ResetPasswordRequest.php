<?php

namespace App\Entity;

use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une demande de réinitialisation de mot de passe.
 * Le token UUID v4 est valide 1h (expiresAt).
 * Toute demande précédente pour le même utilisateur est supprimée
 * avant création d'une nouvelle (voir ResetPasswordRequestRepository).
 */
#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: 'reset_password_request')]
class ResetPasswordRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\Column(length: 100, unique: true)]
    private string $token;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    public function __construct(Utilisateur $utilisateur, string $token, \DateTimeImmutable $expiresAt)
    {
        $this->utilisateur = $utilisateur;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->requestedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    /** Retourne true si le délai de validité du token (1h) est dépassé. */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
