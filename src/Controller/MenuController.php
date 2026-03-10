<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Form\MenusFilterType;
use App\Repository\MenuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/menu')]
class MenuController extends AbstractController
{
    #[Route('/', name: 'app_menu_index', methods: ['GET'])]
    public function index(Request $request, MenuRepository $menuRepository): Response
    {
        $filterForm = $this->createForm(MenusFilterType::class);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $menus = $menuRepository->findByFilters($filterForm->getData());
        } else {
            $menus = $menuRepository->findAll();
        }

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
            'filterForm' => $filterForm,
        ]);
    }

    #[Route('/{id}', name: 'app_menu_show', methods: ['GET'])]
    public function show(Menu $menu): Response
    {
        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }
}
