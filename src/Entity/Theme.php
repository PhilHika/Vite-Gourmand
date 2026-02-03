<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $theme_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le libellé du thème est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères')]
    private ?string $libelle = null;

    public function getId(): ?int
    {
        return $this->theme_id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        if (empty(trim($libelle))) {
            throw new \InvalidArgumentException('Le libellé du thème ne peut pas être vide.');
        }
        $this->libelle = $libelle;

        return $this;
    }
}
