<?php

namespace App\Entity;

use App\Repository\HoraireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HoraireRepository::class)]
class Horaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $horaire_id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le jour est obligatoire')]
    #[Assert\Choice(
        choices: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
        message: 'Le jour sélectionné n\'est pas valide.'
    )]
    private ?string $jour = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'heure d\'ouverture est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heure_ouverture = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'L\'heure de fermeture est obligatoire')]
    #[Assert\Regex(pattern: '/^[0-2][0-9]:[0-5][0-9]$/', message: 'Le format doit être HH:mm')]
    private ?string $heure_fermeture = null;

    public function getId(): ?int
    {
        return $this->horaire_id;
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

    public function getHeure_ouverture(): ?string
    {
        return $this->heure_ouverture;
    }

    public function setHeure_ouverture(string $heure_ouverture): static
    {
        if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $heure_ouverture)) {
            throw new \InvalidArgumentException('Le format de l\'heure d\'ouverture doit être HH:mm.');
        }
        $this->heure_ouverture = $heure_ouverture;

        return $this;
    }

    public function getHeure_fermeture(): ?string
    {
        return $this->heure_fermeture;
    }

    public function setHeure_fermeture(string $heure_fermeture): static
    {
        if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $heure_fermeture)) {
            throw new \InvalidArgumentException('Le format de l\'heure de fermeture doit être HH:mm.');
        }
        
        // Logique métier : fermeture après ouverture
        if ($this->heure_ouverture !== null && $heure_fermeture <= $this->heure_ouverture) {
            throw new \InvalidArgumentException('L\'heure de fermeture doit être après l\'heure d\'ouverture.');
        }

        $this->heure_fermeture = $heure_fermeture;

        return $this;
    }
}
