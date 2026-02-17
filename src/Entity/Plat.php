<?php

namespace App\Entity;

use App\Repository\PlatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlatRepository::class)]
class Plat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'plat_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'titre_plat', length: 50)]
    #[Assert\NotBlank(message: 'Le titre du plat est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $titrePlat = null;

    #[ORM\ManyToMany(targetEntity: Allergene::class, inversedBy: 'plats')]
    #[ORM\JoinTable(name: 'plat_allergene')]
    #[ORM\JoinColumn(name: 'plat_id', referencedColumnName: 'plat_id')]
    #[ORM\InverseJoinColumn(name: 'allergene_id', referencedColumnName: 'allergene_id')]
    private Collection $allergenes;

    #[ORM\Column(name: 'photo', type: 'blob')]
    private $photo = null;

    #[ORM\ManyToMany(targetEntity: Menu::class, mappedBy: 'plats')]
    private Collection $menus;

    public function __construct()
    {
        $this->allergenes = new ArrayCollection();
        $this->menus = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitrePlat(): ?string
    {
        return $this->titrePlat;
    }

    public function setTitrePlat(string $titrePlat): static
    {
        if (empty(trim($titrePlat))) {
            throw new \InvalidArgumentException('Le titre du plat ne peut pas être vide.');
        }
        $this->titrePlat = $titrePlat;

        return $this;
    }

    /**
     * @return Collection<int, Allergene>
     */
    public function getAllergenes(): Collection
    {
        return $this->allergenes;
    }

    public function addAllergene(Allergene $allergene): static
    {
        if (!$this->allergenes->contains($allergene)) {
            $this->allergenes->add($allergene);
            $allergene->referencePlat($this);
        }
        return $this;
    }

    public function removeAllergene(Allergene $allergene): static
    {
        if ($this->allergenes->removeElement($allergene)) {
            $allergene->unreferencePlat($this);
        }
        return $this;
    }

    /**
     * @return Collection<int, Menu>
     */
    public function getMenus(): Collection
    {
        return $this->menus;
    }

    public function addMenu(Menu $menu): static
    {
        if (!$this->menus->contains($menu)) {
            $this->menus->add($menu);
            $menu->addPlat($this);
        }
        return $this;
    }

    public function removeMenu(Menu $menu): static
    {
        if ($this->menus->removeElement($menu)) {
            $menu->removePlat($this);
        }
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
