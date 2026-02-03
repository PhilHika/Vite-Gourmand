<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $role_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le libellé du rôle est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères')]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->role_id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        if (empty(trim($libelle))) {
            throw new \InvalidArgumentException('Le libellé du rôle ne peut pas être vide.');
        }
        if (mb_strlen($libelle) > 50) {
            throw new \InvalidArgumentException('Le libellé ne peut pas dépasser 50 caractères.');
        }
        $this->libelle = $libelle;

        return $this;
    }
}
