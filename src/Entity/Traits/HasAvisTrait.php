<?php

namespace App\Entity\Traits;

use App\Entity\Avis;
use Doctrine\Common\Collections\Collection;

/**
 * Trait collection d'Avis ( Utilisateur)
 */
trait HasAvisTrait
{
    /**
     * @return Collection<int, Avis>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvis(Avis $avis): static
    {
        if (!$this->avis->contains($avis)) {
            $this->avis->add($avis);
            $avis->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAvis(Avis $avis): static
    {
        if ($this->avis->removeElement($avis)) {
            if ($avis->getUtilisateur() === $this) {
                $avis->setUtilisateur(null);
            }
        }

        return $this;
    }
}
