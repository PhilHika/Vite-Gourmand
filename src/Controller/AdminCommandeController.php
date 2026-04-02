<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\AdminCommandeFormType;
use App\Repository\CommandeRepository;
use App\Service\CommandeMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commande')]
#[IsGranted('ROLE_SALARIE')]
class AdminCommandeController extends AbstractController
{
    #[Route('/', name: 'app_admin_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findBy([], ['dateCommande' => 'DESC']);

        return $this->render('admin/commande/index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/{numeroCommande}/annuler', name: 'app_admin_commande_annuler', methods: ['POST'])]
    public function annuler(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])]
        Commande $commande,
        Request $request,
        EntityManagerInterface $em,
        CommandeMailerService $commandeMailer
    ): Response {
        $statutsAnnulables = [
            Commande::STATUT_EN_ATTENTE,
            Commande::STATUT_CONFIRMEE,
            Commande::STATUT_EN_PREPARATION,
        ];

        if (!in_array($commande->getStatut(), $statutsAnnulables, true)) {
            $this->addFlash('danger', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_admin_commande_index');
        }

        if (!$this->isCsrfTokenValid('admin_annuler_' . $commande->getNumeroCommande(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_admin_commande_index');
        }

        $ancienStatut = $commande->getStatut();
        $commande->setStatut(Commande::STATUT_ANNULEE);

        // Restituer le stock du menu
        $menu = $commande->getMenu();
        $menu->setQuantiteRestante($menu->getQuantiteRestante() + 1);

        $em->flush();

        // Notifier le client du changement de statut
        $commandeMailer->envoyerChangementStatut($commande, $ancienStatut);

        $this->addFlash('success', sprintf('La commande %s a été annulée.', $commande->getNumeroCommande()));
        return $this->redirectToRoute('app_admin_commande_index');
    }

    #[Route('/{numeroCommande}/edit', name: 'app_admin_commande_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])]
        Commande $commande,
        Request $request,
        EntityManagerInterface $em,
        CommandeMailerService $commandeMailer
    ): Response {
        $menu = $commande->getMenu();
        $ancienStatut = $commande->getStatut();

        $form = $this->createForm(AdminCommandeFormType::class, $commande, [
            'nombre_personne_min' => $menu->getNombrePersonneMinimum(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le bouton "recalculer" a été cliqué
            if ($request->request->has('_recalculer')) {
                $commande->calculerPrixMenu();
                $this->addFlash('info', sprintf(
                    'Prix menu recalculé : %s €',
                    number_format($commande->getPrixMenu(), 2, ',', ' ')
                ));
            } else {
                $em->flush();

                // Envoi email si le statut a changé
                $commandeMailer->envoyerChangementStatut($commande, $ancienStatut);

                $this->addFlash('success', 'Commande mise à jour avec succès.');
                return $this->redirectToRoute('app_admin_commande_index');
            }
        }

        // Calcul du total pour l'affichage
        $total = ($commande->getPrixMenu() ?? 0) + ($commande->getPrixLivraison() ?? 0);

        return $this->render('admin/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
            'total' => $total,
        ]);
    }
}
