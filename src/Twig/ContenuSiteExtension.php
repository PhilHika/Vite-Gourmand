<?php

namespace App\Twig;

use App\Repository\ContenuSiteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig exposant les contenus éditables du site stockés dans MongoDB.
 * Fonctions disponibles : get_description_site(), get_conditions_vente().
 */
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

    /** Retourne la description générale du site (clé MongoDB 'description'). */
    public function getDescriptionSite(): ?string
    {
        $doc = $this->contenuSiteRepository->findByCle('description');

        return $doc?->getContenu();
    }

    /** Retourne les conditions générales de vente (clé MongoDB 'conditions_vente'). */
    public function getConditionsVente(): ?string
    {
        $doc = $this->contenuSiteRepository->findByCle('conditions_vente');

        return $doc?->getContenu();
    }
}
