<?php

namespace App\Entity;

use App\Repository\RegimeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RegimeRepository::class)]
class Regime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $regime_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le libellé du régime est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères')]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->regime_id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        if (empty(trim($libelle))) {
            throw new \InvalidArgumentException('Le libellé du régime ne peut pas être vide.');
        }
        if (mb_strlen($libelle) > 50) {
            throw new \InvalidArgumentException('Le libellé ne peut pas dépasser 50 caractères.');
        }
        $this->libelle = $libelle;

        return $this;
    }
}
