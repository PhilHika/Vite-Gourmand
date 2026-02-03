<?php

namespace App\Entity;

use App\Repository\PlatRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlatRepository::class)]
class Plat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $plat_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le titre du plat est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $titre_plat = null;

    #[ORM\Column(type: 'blob')]
    private $photo = null;

    public function getId(): ?int
    {
        return $this->plat_id;
    }

    public function getTitre_plat(): ?string
    {
        return $this->titre_plat;
    }

    public function setTitre_plat(string $titre_plat): static
    {
        if (empty(trim($titre_plat))) {
            throw new \InvalidArgumentException('Le titre du plat ne peut pas être vide.');
        }
        $this->titre_plat = $titre_plat;

        return $this;
    }

    /**
     * @return resource|string|null
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param resource|string|null $photo
     */
    public function setPhoto($photo): static
    {
        $this->photo = $photo;

        return $this;
    }
}
