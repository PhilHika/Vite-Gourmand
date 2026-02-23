<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Form\MenuFormType;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menu')]
class AdminMenuController extends AbstractController
{
    #[Route('/', name: 'app_admin_menu_index', methods: ['GET'])]
    public function index(MenuRepository $menuRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé. Vous n\'avez pas les droits pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin_menu/index.html.twig', [
            'menus' => $menuRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, MenuRepository $menuRepository): Response
    {
        // Custom Security Check for ROLE_USER redirection
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé. Vous n\'avez pas les droits pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        $menu = new Menu();
        $form = $this->createForm(MenuFormType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $menuRepository->save($menu, true);

            $this->addFlash('success', 'Le menu a bien été créé.');

            return $this->redirectToRoute('app_admin_menu_edit', ['id' => $menu->getId()]);
        }

        return $this->render('admin_menu/menu_edit.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Menu $menu, MenuRepository $menuRepository): Response
    {
        // Custom Security Check for ROLE_USER redirection
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé. Vous n\'avez pas les droits pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(MenuFormType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $menuRepository->save($menu, true);

            $this->addFlash('success', 'Le menu a bien été modifié.');

            return $this->redirectToRoute('app_admin_menu_edit', ['id' => $menu->getId()]);
        }

        return $this->render('admin_menu/menu_edit.html.twig', [
            'menu' => $menu,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-availability', name: 'app_admin_menu_toggle_availability', methods: ['POST'])]
    public function toggleAvailability(Request $request, Menu $menu, MenuRepository $menuRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_home');
        }

        if (!$this->isCsrfTokenValid('toggle-menu-' . $menu->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_menu_index');
        }

        if ($menu->getQuantiteRestante() > 0) {
            $menu->setQuantiteRestante(0);
            $this->addFlash('success', 'Le menu "' . $menu->getTitre() . '" est maintenant indisponible.');
        } else {
            $menu->setQuantiteRestante(1);
            $this->addFlash('success', 'Le menu "' . $menu->getTitre() . '" est maintenant disponible.');
        }

        $menuRepository->save($menu, true);

        return $this->redirectToRoute('app_admin_menu_index');
    }

    #[Route('/{id}/delete', name: 'app_admin_menu_delete', methods: ['POST'])]
    public function delete(Request $request, Menu $menu, MenuRepository $menuRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_home');
        }

        if (!$this->isCsrfTokenValid('delete-menu-' . $menu->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_menu_index');
        }

        $titre = $menu->getTitre();
        $menuRepository->remove($menu, true);

        $this->addFlash('success', 'Le menu "' . $titre . '" a bien été supprimé.');

        return $this->redirectToRoute('app_admin_menu_index');
    }
}
