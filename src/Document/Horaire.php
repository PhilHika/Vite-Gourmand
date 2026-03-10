<?php

namespace App\Document;

use App\Repository\HoraireRepository;
use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'horaires', repositoryClass: HoraireRepository::class)]
class Horaire
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'Le jour est obligatoire')]
    #[Assert\Choice(
        choices: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
        message: 'Le jour sélectionné n\'est pas valide.'
    )]
    private ?string $jour = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'L\'heure d\'ouverture est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heureOuverture = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'L\'heure de fermeture est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heureFermeture = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getJour(): ?string
    {
        return $this->jour;
    }

    public function setJour(string $jour): static
    {
        $joursValides = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        if (!in_array($jour, $joursValides)) {
            throw new \InvalidArgumentException(sprintf('Le jour "%s" n\'est pas un jour de la semaine valide.', $jour));
        }
        $this->jour = $jour;

        return $this;
    }

    public function getHeureOuverture(): ?string
    {
        return $this->heureOuverture;
    }

    public function setHeureOuverture(string $heureOuverture): static
    {
        if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $heureOuverture)) {
            throw new \InvalidArgumentException('Le format de l\'heure d\'ouverture doit être HH:mm.');
        }
        $this->heureOuverture = $heureOuverture;

        return $this;
    }

    public function getHeureFermeture(): ?string
    {
        return $this->heureFermeture;
    }

    public function setHeureFermeture(string $heureFermeture): static
    {
        // [0-2][0-9]:[0-5][0-9]
        if (!preg_match('/^[0-2][\d]:[0-5][\d]$/', $heureFermeture)) {
            throw new \InvalidArgumentException('Le format de l\'heure de fermeture doit être HH:mm.');
        }

        // Logique métier : fermeture après ouverture
        if ($this->heureOuverture !== null && $heureFermeture <= $this->heureOuverture) {
            throw new \InvalidArgumentException('L\'heure de fermeture doit être après l\'heure d\'ouverture.');
        }

        $this->heureFermeture = $heureFermeture;

        return $this;
    }
}
