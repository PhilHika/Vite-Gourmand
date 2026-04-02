<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'avis_id')]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotBlank(message: 'La note est obligatoire')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre 1 et 5')]
    private ?int $note = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le statut ne peut pas dépasser {{ limit }} caractères')]
    private ?string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToOne(inversedBy: 'avis', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'numero_commande', referencedColumnName: 'numero_commande', nullable: false)]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        if (empty(trim($description))) {
            throw new \InvalidArgumentException('La description ne peut pas être vide.');
        }
        $this->description = $description;

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

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }
}
