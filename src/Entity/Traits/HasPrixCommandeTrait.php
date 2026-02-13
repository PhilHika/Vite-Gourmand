<?php

namespace App\Entity\Traits;

/**
 * Trait pour les prix dans une Commande
 */
trait HasPrixCommandeTrait
{
    public function getPrixMenu(): ?float
    {
        return $this->prixMenu;
    }

    public function setPrixMenu(float $prixMenu): static
    {
        if ($prixMenu < 0) {
            throw new \InvalidArgumentException('Le prix de menu ne peut pas être négatif.');
        }
        $this->prixMenu = $prixMenu;

        return $this;
    }

    public function getPrixLivraison(): ?float
    {
        return $this->prixLivraison;
    }

    public function setPrixLivraison(float $prixLivraison): static
    {
        if ($prixLivraison < 0) {
            throw new \InvalidArgumentException('Le prix de livraison ne peut pas être négatif.');
        }
        $this->prixLivraison = $prixLivraison;

        return $this;
    }
}
