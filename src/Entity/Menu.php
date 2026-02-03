<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $menu_id = null;

    #[ORM\Column(length: 50)]
    private ?string $titre = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre de personnes doit être positif')]
    private ?int $nombre_personne_minimum = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le prix doit être supérieur à zéro')]
    private ?float $prix_par_personne = null;

    #[ORM\Column(length: 50)]
    private ?string $regime = null;

    #[ORM\Column(length: 50)]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'La quantité doit être positive')]
    private ?int $quantite_restante = null;

    public function getId(): ?int
    {
        return $this->menu_id;
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

    public function getNombre_personne_minimum(): ?int
    {
        return $this->nombre_personne_minimum;
    }

    public function setNombre_personne_minimum(int $nombre_personne_minimum): static
    {
        $this->nombre_personne_minimum = $nombre_personne_minimum;

        return $this;
    }

    public function getPrix_par_personne(): ?float
    {
        return $this->prix_par_personne;
    }

    public function setPrix_par_personne(float $prix_par_personne): static
    {
        $this->prix_par_personne = $prix_par_personne;

        return $this;
    }

    public function getRegime(): ?string
    {
        return $this->regime;
    }

    public function setRegime(string $regime): static
    {
        $this->regime = $regime;

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

    public function getQuantite_restante(): ?int
    {
        return $this->quantite_restante;
    }

    public function setQuantite_restante(int $quantite_restante): static
    {
        $this->quantite_restante = $quantite_restante;

        return $this;
    }
}
