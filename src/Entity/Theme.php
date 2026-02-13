<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'theme_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'libelle', length: 50)]
    #[Assert\NotBlank(message: 'Le libellé du thème est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères')]
    private ?string $libelle = null;

    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Menu::class)]
    private Collection $menus;

    public function __construct()
    {
        $this->menus = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $menu->setTheme($this);
        }

        return $this;
    }

    public function removeMenu(Menu $menu): static
    {
        if ($this->menus->removeElement($menu)) {
            if ($menu->getTheme() === $this) {
                $menu->setTheme(null);
            }
        }

        return $this;
    }
}
