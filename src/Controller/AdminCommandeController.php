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
            'pret_materiel' => $commande->getPretMateriel() ?? false,
            'restitution_materiel' => $commande->getRestitutionMateriel() ?? false,
            'statut_actuel' => $ancienStatut,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->request->has('_recalculer')) {
                $commande->calculerPrixMenu();
                $this->addFlash('info', sprintf(
                    'Prix menu recalculé : %s €',
                    number_format($commande->getPrixMenu(), 2, ',', ' ')
                ));
            } else {
                $nouveauStatut = $commande->getStatut();
                $commande->setStatut($ancienStatut);

                $erreur = $this->validerDonnees($commande, $ancienStatut, $nouveauStatut);
                if ($erreur !== null) {
                    $this->addFlash('danger', $erreur);
                    return $this->renderEdit($commande, $form);
                }

                $commande->setStatut($nouveauStatut);
                $em->flush();
                $commandeMailer->envoyerChangementStatut($commande, $ancienStatut);

                $this->addFlash('success', 'Commande mise à jour avec succès.');
                return $this->redirectToRoute('app_admin_commande_edit', [
                    'numeroCommande' => $commande->getNumeroCommande(),
                ]);
            }
        }

        return $this->renderEdit($commande, $form);
    }

    private function validerDonnees(Commande $commande, string $ancienStatut, string $nouveauStatut): ?string
    {
        if ($commande->getRestitutionMateriel() && !$commande->getPretMateriel()) {
            return 'Une restitution de matériel ne peut être validée sans prêt de matériel.';
        }

        return $this->validerTransitionStatut($commande, $ancienStatut, $nouveauStatut);
    }

    private function validerTransitionStatut(Commande $commande, string $ancienStatut, string $nouveauStatut): ?string
    {
        $erreur = null;

        if ($nouveauStatut !== $ancienStatut && $nouveauStatut === Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL && !$commande->peutEtreEnAttenteRetourMateriel()) {
            $erreur = $commande->getRestitutionMateriel()
                ? 'Impossible : le matériel est déjà indiqué comme restitué.'
                : 'Impossible de passer au statut "En attente de retour matériel" : la commande doit être au statut "Livrée" avec un prêt de matériel.';
        }

        if ($nouveauStatut !== $ancienStatut && $nouveauStatut === Commande::STATUT_TERMINEE && !$commande->peutEtreTerminee()) {
            $erreur = $commande->getPretMateriel()
                ? 'Une commande ne peut être terminée sans retour matériel.'
                : 'Impossible de passer au statut "Terminée" : la commande doit être au statut "Livrée".';
        }

        return $erreur;
    }

    private function renderEdit(Commande $commande, mixed $form): Response
    {
        return $this->render('admin/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
            'total' => ($commande->getPrixMenu() ?? 0) + ($commande->getPrixLivraison() ?? 0),
        ]);
    }
}
