<?php

namespace App\Controller;

use App\Entity\Menu;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/menu')]
class MenuController extends AbstractController
{
    /**
     * Point d'entrée HTML de la liste des menus.
     * Le filtrage, le chargement des données et le rendu des cartes sont entièrement
     * délégués à la SPA Vue (montée sur #app dans menu/index.html.twig).
     * La logique métier reste dans MenuApiController qui sert les données en JSON.
     */
    #[Route('/', name: 'app_menu_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('menu/index.html.twig');
    }

    #[Route('/{id}', name: 'app_menu_show', methods: ['GET'])]
    public function show(Menu $menu): Response
    {
        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
        ]);
    }
}
