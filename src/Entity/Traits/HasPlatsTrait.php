<?php

namespace App\Entity\Traits;

use App\Entity\Plat;
use Doctrine\Common\Collections\Collection;

/**
 * Trait collection de Plats (Menu)
 */
trait HasPlatsTrait
{
    /**
     * @return Collection<int, Plat>
     */
    public function getPlats(): Collection
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): static
    {
        if (!$this->plats->contains($plat)) {
            $this->plats->add($plat);
            $plat->addMenu($this);
        }
        return $this;
    }

    public function removePlat(Plat $plat): static
    {
        if ($this->plats->removeElement($plat)) {
            $plat->removeMenu($this);
        }
        return $this;
    }
}
