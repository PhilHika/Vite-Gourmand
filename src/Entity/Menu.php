<?php

namespace App\Entity;

use App\Entity\Traits\HasCommandesMenuTrait; // collection commandes
use App\Entity\Traits\HasPlatsTrait; // collection plats
use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    use HasPlatsTrait;
    use HasCommandesMenuTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'menu_id')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $titre = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre de personnes doit être positif')]
    private ?int $nombrePersonneMinimum = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le prix doit être supérieur à zéro')]
    private ?float $prixParPersonne = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[ORM\JoinColumn(name: 'regime_id', referencedColumnName: 'regime_id')]
    private ?Regime $regime = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[ORM\JoinColumn(name: 'theme_id', referencedColumnName: 'theme_id', nullable: false)]
    private ?Theme $theme = null;



    #[ORM\ManyToMany(targetEntity: Plat::class, inversedBy: 'menus')]
    #[ORM\JoinTable(name: 'menu_plat')]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'menu_id')]
    #[ORM\InverseJoinColumn(name: 'plat_id', referencedColumnName: 'plat_id')]
    private Collection $plats;

    #[ORM\Column(length: 50)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'La quantité doit être positive')]
    private ?int $quantiteRestante = null;

    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: Commande::class)]
    private Collection $commandes;

    public function __construct()
    {
        $this->plats = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNombrePersonneMinimum(): ?int
    {
        return $this->nombrePersonneMinimum;
    }

    public function setNombrePersonneMinimum(int $nombrePersonneMinimum): static
    {
        $this->nombrePersonneMinimum = $nombrePersonneMinimum;

        return $this;
    }

    public function getPrixParPersonne(): ?float
    {
        return $this->prixParPersonne;
    }

    public function setPrixParPersonne(float $prixParPersonne): static
    {
        $this->prixParPersonne = $prixParPersonne;

        return $this;
    }

    public function getRegime(): ?Regime
    {
        return $this->regime;
    }

    public function setRegime(?Regime $regime): static
    {
        $this->regime = $regime;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getQuantiteRestante(): ?int
    {
        return $this->quantiteRestante;
    }

    public function setQuantiteRestante(int $quantiteRestante): static
    {
        $this->quantiteRestante = $quantiteRestante;

        return $this;
    }
}
