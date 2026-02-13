<?php

namespace App\Entity\Traits;

use App\Entity\Commande;
use Doctrine\Common\Collections\Collection;

/**
 * Trait collection de Commandes (Menu)
 */
trait HasCommandesMenuTrait
{
    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setMenu($this);
        }
        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getMenu() === $this) {
                $commande->setMenu(null);
            }
        }
        return $this;
    }
}
