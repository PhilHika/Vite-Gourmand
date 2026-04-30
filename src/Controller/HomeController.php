<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/acceuil', name: 'app_home')]
    public function index(\App\Repository\AvisRepository $avisRepository): Response
    {
        // Afficher uniquement les avis publiés (modérés) triés par date décroissante
        $tousLesAvisPublies = $avisRepository->findBy(
            ['statut' => \App\Entity\Avis::STATUT_PUBLIE],
            ['id' => 'DESC']
        );

        return $this->render('home/index.html.twig', [
            'tous_les_avis' => $tousLesAvisPublies,
        ]);
    }
}
