<?php

namespace App\Twig;

use App\Repository\HoraireRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HoraireExtension extends AbstractExtension
{
    public function __construct(private HoraireRepository $horaireRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_horaires', [$this, 'getHoraires']),
        ];
    }

    /**
     * Retourne les horaires indexés par jour, dans l'ordre de la semaine.
     *
     * @return array<string, array{ouverture: string, fermeture: string}|null>
     */
    public function getHoraires(): array
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        $horairesParJour = [];
        foreach ($this->horaireRepository->findAll() as $horaire) {
            $horairesParJour[$horaire->getJour()] = [
                'ouverture' => $horaire->getHeureOuverture(),
                'fermeture' => $horaire->getHeureFermeture(),
            ];
        }

        $result = [];
        foreach ($jours as $jour) {
            $result[$jour] = $horairesParJour[$jour] ?? null;
        }

        return $result;
    }
}
