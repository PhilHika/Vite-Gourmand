<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'avis_id')]
    private ?int $id = null;

    #[ORM\Column(name: 'note', length: 50)]
    #[Assert\NotBlank(message: 'La note est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'La note ne peut pas dépasser {{ limit }} caractères')]
    private ?string $note = null;

    #[ORM\Column(name: 'description', length: 50)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères')]
    private ?string $description = null;

    #[ORM\Column(name: 'statut', length: 50)]
    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le statut ne peut pas dépasser {{ limit }} caractères')]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'utilisateur_id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        if (empty(trim($note))) {
            throw new \InvalidArgumentException('La note ne peut pas être vide.');
        }
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
}
