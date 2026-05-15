<?php

namespace App\Controller\Api;

use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/** Endpoint API exposant les référentiels (thèmes, régimes) consommés par les filtres Vue. */
#[Route('/api/referentiels', name: 'api_referentiels_')]
class ReferentielApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ThemeRepository $themeRepository, RegimeRepository $regimeRepository): JsonResponse
    {
        // Construction du payload des thèmes
        $themesList = $themeRepository->findAll();
        $payloadThemes = [];
        foreach ($themesList as $themeItem) {
            $payloadThemes[] = [
                'id'      => $themeItem->getId(),
                'libelle' => $themeItem->getLibelle(),
            ];
        }

        // Construction du payload des régimes
        $regimesList = $regimeRepository->findAll();
        $payloadRegimes = [];
        foreach ($regimesList as $regimeItem) {
            $payloadRegimes[] = [
                'id'      => $regimeItem->getId(),
                'libelle' => $regimeItem->getLibelle(),
            ];
        }

        return $this->json([
            'themes'  => $payloadThemes,
            'regimes' => $payloadRegimes,
        ]);
    }
}
