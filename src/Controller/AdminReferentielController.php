<?php

namespace App\Controller;

use App\Entity\Allergene;
use App\Entity\Regime;
use App\Entity\Theme;
use App\Form\AllergeneFormType;
use App\Form\RegimeFormType;
use App\Form\ThemeFormType;
use App\Repository\AllergeneRepository;
use App\Repository\RegimeRepository;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/referentiel')]
#[IsGranted('ROLE_SALARIE')]
class AdminReferentielController extends AbstractController
{
    // --- ALLERGENES ---

    #[Route('/allergene/list', name: 'app_admin_referentiel_allergene_list', methods: ['GET'])]
    public function listAllergenes(AllergeneRepository $repository): JsonResponse
    {
        $list = $repository->findAll();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->getId(),
                'libelle' => $item->getLibelle(),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/allergene/new', name: 'app_admin_referentiel_allergene_new', methods: ['POST'])]
    public function newAllergene(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $allergene = new Allergene();
        $form = $this->createForm(AllergeneFormType::class, $allergene);

        // Handle JSON or Form data (simplest is form submit via ajax)
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($allergene);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'item' => ['id' => $allergene->getId(), 'libelle' => $allergene->getLibelle()]
            ]);
        }

        // If errors
        return new JsonResponse(['success' => false, 'message' => 'Erreur de validation'], 400);
    }

    #[Route('/allergene/{id}/delete', name: 'app_admin_referentiel_allergene_delete', methods: ['DELETE'])]
    public function deleteAllergene(Allergene $allergene, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($allergene);
            $em->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer cet élément (peut-être utilisé ?)'], 500);
        }
    }

    // --- REGIMES ---

    #[Route('/regime/list', name: 'app_admin_referentiel_regime_list', methods: ['GET'])]
    public function listRegimes(RegimeRepository $repository): JsonResponse
    {
        $list = $repository->findAll();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->getId(),
                'libelle' => $item->getLibelle(),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/regime/new', name: 'app_admin_referentiel_regime_new', methods: ['POST'])]
    public function newRegime(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $regime = new Regime();
        $form = $this->createForm(RegimeFormType::class, $regime);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($regime);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'item' => ['id' => $regime->getId(), 'libelle' => $regime->getLibelle()]
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Erreur de validation'], 400);
    }

    #[Route('/regime/{id}/delete', name: 'app_admin_referentiel_regime_delete', methods: ['DELETE'])]
    public function deleteRegime(Regime $regime, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($regime);
            $em->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer cet élément (peut-être utilisé ?)'], 500);
        }
    }

    // --- THEMES ---

    #[Route('/theme/list', name: 'app_admin_referentiel_theme_list', methods: ['GET'])]
    public function listThemes(ThemeRepository $repository): JsonResponse
    {
        $list = $repository->findAll();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->getId(),
                'libelle' => $item->getLibelle(),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/theme/new', name: 'app_admin_referentiel_theme_new', methods: ['POST'])]
    public function newTheme(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeFormType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($theme);
            $em->flush();
            return new JsonResponse([
                'success' => true,
                'item' => ['id' => $theme->getId(), 'libelle' => $theme->getLibelle()]
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Erreur de validation'], 400);
    }

    #[Route('/theme/{id}/delete', name: 'app_admin_referentiel_theme_delete', methods: ['DELETE'])]
    public function deleteTheme(Theme $theme, EntityManagerInterface $em): JsonResponse
    {
        try {
            $em->remove($theme);
            $em->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Impossible de supprimer cet élément (peut-être utilisé ?)'], 500);
        }
    }
}
