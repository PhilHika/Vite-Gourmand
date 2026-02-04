<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    #[ORM\Id]
    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero_commande = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de commande est obligatoire')]
    private ?\DateTimeInterface $date_commande = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de prestation est obligatoire')]
    private ?\DateTimeInterface $date_prestation = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'heure de livraison est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heure_livraison = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le prix de menu ne peut pas être négatif')]
    private ?float $prix_menu = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le nombre de personnes doit être supérieur à zéro')]
    private ?int $nombre_personne = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le prix de livraison ne peut pas être négatif')]
    private ?float $prix_livraison = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    private ?string $statut = null;

    #[ORM\Column]
    private ?bool $pret_materiel = null;

    #[ORM\Column]
    private ?bool $restitution_materiel = null;

    #[ORM\PrePersist]
    public function generateNumero_commande(): void
    {
        // eviter un crash si la date n'est pas set 
        // au moment de la creation de la commande
        if ($this->date_commande === null) {
            $this->date_commande = new \DateTime();
        }

        if ($this->numero_commande === null) {
            // Format : A7F3K9M2-20250204 (17 caractères)
            $this->numero_commande = sprintf(
                '%s-%s',
                strtoupper(substr(Uuid::v4()->toBase58(), 0, 8)),
                $this->date_commande->format('Ymd')
            );
        }
    }

    public function getNumero_commande(): ?string
    {
        return $this->numero_commande;
    }

    public function getDate_commande(): ?\DateTimeInterface
    {
        return $this->date_commande;
    }

    public function setDate_commande(\DateTimeInterface $date_commande): static
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getDate_prestation(): ?\DateTimeInterface
    {
        return $this->date_prestation;
    }

    public function setDate_prestation(\DateTimeInterface $date_prestation): static
    {
        if ($this->date_commande !== null && $date_prestation < $this->date_commande) {
            throw new \InvalidArgumentException('La date de prestation ne peut pas être antérieure à la date de commande.');
        }
        $this->date_prestation = $date_prestation;

        return $this;
    }

    public function getHeure_livraison(): ?string
    {
        return $this->heure_livraison;
    }

    public function setHeure_livraison(string $heure_livraison): static
    {
        if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $heure_livraison)) {
            throw new \InvalidArgumentException('Le format de l\'heure de livraison doit être HH:mm.');
        }
        $this->heure_livraison = $heure_livraison;

        return $this;
    }

    public function getNombre_personne(): ?int
    {
        return $this->nombre_personne;
    }

    public function setNombre_personne(int $nombre_personne): static
    {
        if ($nombre_personne <= 0) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à zéro.');
        }
        $this->nombre_personne = $nombre_personne;

        return $this;
    }

    public function getPrix_livraison(): ?float
    {
        return $this->prix_livraison;
    }

    public function setPrix_livraison(float $prix_livraison): static
    {
        if ($prix_livraison < 0) {
            throw new \InvalidArgumentException('Le prix de livraison ne peut pas être négatif.');
        }
        $this->prix_livraison = $prix_livraison;

        return $this;
    }

    public function getPrix_menu(): ?float
    {
        return $this->prix_menu;
    }

    public function setPrix_menu(float $prix_menu): static
    {
        if ($prix_menu < 0) {
            throw new \InvalidArgumentException('Le prix de menu ne peut pas être négatif.');
        }
        $this->prix_menu = $prix_menu;

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

    public function getPret_materiel(): ?bool
    {
        return $this->pret_materiel;
    }

    public function setPret_materiel(bool $pret_materiel): static
    {
        $this->pret_materiel = $pret_materiel;

        return $this;
    }

    public function getRestitution_materiel(): ?bool
    {
        return $this->restitution_materiel;
    }

    public function setRestitution_materiel(bool $restitution_materiel): static
    {
        $this->restitution_materiel = $restitution_materiel;

        return $this;
    }
}
