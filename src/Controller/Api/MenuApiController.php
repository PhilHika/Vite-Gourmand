<?php

namespace App\Controller\Api;

use App\Form\MenusFilterType;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Endpoint API exposant la liste des menus filtrés au format JSON pour la SPA Vue. */
#[Route('/api/menus', name: 'api_menus_')]
class MenuApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, MenuRepository $menuRepository): JsonResponse
    {
        // Réutilise EXACTEMENT le même formulaire que la version Twig
        $filterForm = $this->createForm(MenusFilterType::class);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $menusList = $menuRepository->findByFilters($filterForm->getData());
        } else {
            $menusList = $menuRepository->findAll();
        }

        // Construction du payload JSON : un foreach par niveau pour rester lisible
        $payloadMenus = [];
        foreach ($menusList as $menuItem) {

            // Sous-tableau des plats
            $payloadPlats = [];
            foreach ($menuItem->getPlats() as $platItem) {

                // Sous-tableau des libellés d'allergènes du plat
                $libellesAllergenes = [];
                foreach ($platItem->getAllergenes() as $allergeneItem) {
                    $libellesAllergenes[] = $allergeneItem->getLibelle();
                }

                $payloadPlats[] = [
                    'id'         => $platItem->getId(),
                    'titrePlat'  => $platItem->getTitrePlat(),
                    'photoPath'  => $platItem->getPhotoPath(),
                    'allergenes' => $libellesAllergenes,
                ];
            }

            // Libellés régime/thème (peuvent être null)
            $regimeEntity = $menuItem->getRegime();
            $themeEntity = $menuItem->getTheme();
            $libelleRegime = $regimeEntity !== null ? $regimeEntity->getLibelle() : null;
            $libelleTheme = $themeEntity !== null ? $themeEntity->getLibelle() : null;

            $payloadMenus[] = [
                'id'                    => $menuItem->getId(),
                'titre'                 => $menuItem->getTitre(),
                'description'           => $menuItem->getDescription(),
                'prixParPersonne'       => (float) $menuItem->getPrixParPersonne(),
                'nombrePersonneMinimum' => $menuItem->getNombrePersonneMinimum(),
                'quantiteRestante'      => $menuItem->getQuantiteRestante(),
                'regime'                => $libelleRegime,
                'theme'                 => $libelleTheme,
                'conditions'            => $menuItem->getConditions() ?? [],
                'plats'                 => $payloadPlats,
            ];
        }

        return $this->json([
            'menus'           => $payloadMenus,
            'isSalarie'       => $this->isGranted('ROLE_SALARIE'),
            'isAuthenticated' => $this->getUser() !== null,
        ]);
    }
}
