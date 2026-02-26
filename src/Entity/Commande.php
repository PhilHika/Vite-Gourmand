<?php

namespace App\Entity;

use App\Entity\Traits\HasPrixCommandeTrait; // prix commande
use App\Repository\CommandeRepository;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    use HasPrixCommandeTrait;

    // Constantes de statut
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmee';
    public const STATUT_EN_PREPARATION = 'en_preparation';
    public const STATUT_LIVREE = 'livree';
    public const STATUT_ANNULEE = 'annulee';

    #[ORM\Id]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $numeroCommande = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de commande est obligatoire')]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de prestation est obligatoire')]
    private ?\DateTimeInterface $datePrestation = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'heure de livraison est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heureLivraison = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le prix de menu ne peut pas être négatif')]
    private ?float $prixMenu = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le nombre de personnes doit être supérieur à zéro')]
    private ?int $nombrePersonne = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le prix de livraison ne peut pas être négatif')]
    private ?float $prixLivraison = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column]
    private ?bool $pretMateriel = null;

    #[ORM\Column]
    private ?bool $restitutionMateriel = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'menu_id', nullable: false)]
    private ?Menu $menu = null;

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    #[ORM\PrePersist]
    public function generateNumeroCommande(): void
    {
        // eviter un crash si la date n'est pas set
        // au moment de la creation de la commande
        if ($this->dateCommande === null) {
            $this->dateCommande = new \DateTime();
        }

        if ($this->numeroCommande === null) {
            // Format : A7F3K9M2-20250204 (17 caractères)
            $this->numeroCommande = sprintf(
                '%s-%s',
                strtoupper(substr(Uuid::v4()->toBase58(), 0, 8)),
                $this->dateCommande->format('Ymd')
            );
        }
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numeroCommande;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    public function getDatePrestation(): ?\DateTimeInterface
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(\DateTimeInterface $datePrestation): static
    {
        if ($this->dateCommande !== null && $datePrestation < $this->dateCommande) {
            throw new \InvalidArgumentException('La date de prestation ne peut pas être antérieure à la date de commande.');
        }
        $this->datePrestation = $datePrestation;

        return $this;
    }

    public function getHeureLivraison(): ?string
    {
        return $this->heureLivraison;
    }

    public function setHeureLivraison(string $heureLivraison): static
    {
        // [0-2][0-9]:[0-5][0-9]
        if (!preg_match('/^[0-2][\d]:[0-5][\d]$/', $heureLivraison)) {
            throw new \InvalidArgumentException('Le format de l\'heure de livraison doit être HH:mm.');
        }
        $this->heureLivraison = $heureLivraison;

        return $this;
    }

    public function getNombrePersonne(): ?int
    {
        return $this->nombrePersonne;
    }

    public function setNombrePersonne(int $nombrePersonne): static
    {
        if ($nombrePersonne <= 0) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à zéro.');
        }
        $this->nombrePersonne = $nombrePersonne;

        return $this;
    }



    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (empty(trim($statut))) {
            throw new \InvalidArgumentException('Le statut ne peut pas être vide.');
        }
        $this->statut = $statut;

        return $this;
    }

    public function getPretMateriel(): ?bool
    {
        return $this->pretMateriel;
    }

    public function setPretMateriel(bool $pretMateriel): static
    {
        $this->pretMateriel = $pretMateriel;

        return $this;
    }

    public function getRestitutionMateriel(): ?bool
    {
        return $this->restitutionMateriel;
    }

    public function setRestitutionMateriel(bool $restitutionMateriel): static
    {
        $this->restitutionMateriel = $restitutionMateriel;

        return $this;
    }

    /**
     * Calcule le prixMenu à partir du menu et du nombrePersonne.
     * Applique une réduction de 10% si nombrePersonne >= menu.nombrePersonneMinimum + 5.
     */
    public function calculerPrixMenu(): void
    {
        if ($this->menu === null || $this->nombrePersonne === null) {
            return;
        }

        $prixBase = $this->menu->getPrixParPersonne() * $this->nombrePersonne;

        if ($this->nombrePersonne >= $this->menu->getNombrePersonneMinimum() + 5) {
            $prixBase *= 0.90; // réduction de 10%
        }

        $this->prixMenu = round($prixBase, 2);
    }
}
