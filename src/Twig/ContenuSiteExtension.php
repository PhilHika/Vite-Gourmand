<?php

namespace App\Twig;

use App\Repository\ContenuSiteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContenuSiteExtension extends AbstractExtension
{
    public function __construct(private ContenuSiteRepository $contenuSiteRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_description_site', [$this, 'getDescriptionSite']),
            new TwigFunction('get_conditions_vente', [$this, 'getConditionsVente']),
        ];
    }

    public function getDescriptionSite(): ?string
    {
        $doc = $this->contenuSiteRepository->findByCle('description');

        return $doc?->getContenu();
    }

    public function getConditionsVente(): ?string
    {
        $doc = $this->contenuSiteRepository->findByCle('conditions_vente');

        return $doc?->getContenu();
    }
}
