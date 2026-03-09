<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeFormType;
use App\Form\EditCommandeFormType;
use App\Repository\MenuRepository;
use App\Service\CommandeMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, MenuRepository $menuRepository, EntityManagerInterface $em, CommandeMailerService $commandeMailer): Response
    {
        $menuId = $request->query->get('menu');
        $menu = null;

        if ($menuId) {
            $menu = $menuRepository->find($menuId);

            if (!$menu) {
                $this->addFlash('error', 'Ce menu n\'existe pas.');
                return $this->redirectToRoute('app_menu_index');
            }

            // Vérification du stock
            if ($menu->getQuantiteRestante() <= 0) {
                $this->addFlash('error', 'Désolé, ce menu est épuisé.');
                return $this->redirectToRoute('app_menu_index');
            }
        }

        // Si pas de menu sélectionné → afficher le listing des menus
        if (!$menu) {
            return $this->render('commande/new.html.twig', [
                'menu' => null,
                'menus' => $menuRepository->findAll(),
                'form' => null,
            ]);
        }

        // Créer la commande et pré-remplir
        $commande = new Commande();
        $commande->setMenu($menu);
        $commande->setNombrePersonne($menu->getNombrePersonneMinimum());
        $commande->setDatePrestation(new \DateTime('+1 day'));
        $commande->setHeureLivraison('12:00');

        // Set automatiques AVANT handleRequest (sinon validation @NotBlank échoue)
        $commande->setUtilisateur($this->getUser());
        $commande->setDateCommande(new \DateTime());
        $commande->setRestitutionMateriel(false);
        $commande->setPrixLivraison(0);

        $form = $this->createForm(CommandeFormType::class, $commande, [
            'nombre_personne_min' => $menu->getNombrePersonneMinimum(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification stock au moment de la soumission
            if ($menu->getQuantiteRestante() <= 0) {
                $this->addFlash('error', 'Désolé, ce menu vient d\'être épuisé.');
                return $this->redirectToRoute('app_menu_index');
            }

            // Validation nombrePersonne >= minimum
            if ($commande->getNombrePersonne() < $menu->getNombrePersonneMinimum()) {
                $this->addFlash('error', sprintf(
                    'Le nombre de personnes doit être d\'au moins %d pour ce menu.',
                    $menu->getNombrePersonneMinimum()
                ));
                return $this->render('commande/new.html.twig', [
                    'menu' => $menu,
                    'menus' => null,
                    'form' => $form,
                    'seuil_reduction' => $menu->getNombrePersonneMinimum() + 5,
                ]);
            }

            // Calcul du prix avec réduction éventuelle
            $commande->calculerPrixMenu();

            // Étape 2 : confirmation finale
            if ($request->request->get('_confirmed') === '1') {
                // Décrémenter le stock
                $menu->setQuantiteRestante($menu->getQuantiteRestante() - 1);

                $em->persist($commande);
                $em->flush();

                // Envoi email de confirmation au client
                $commandeMailer->envoyerConfirmation($commande);

                $this->addFlash('success', 'Votre commande a bien été enregistrée !');
                return $this->redirectToRoute('app_commande_show', [
                    'numeroCommande' => $commande->getNumeroCommande(),
                ]);
            }

            // Étape 1 : afficher le récap prix pour confirmation
            $prixBase = $menu->getPrixParPersonne() * $commande->getNombrePersonne();
            $reductionAppliquee = $commande->getPrixMenu() < $prixBase;

            return $this->render('commande/new.html.twig', [
                'menu' => $menu,
                'menus' => null,
                'form' => $form,
                'seuil_reduction' => $menu->getNombrePersonneMinimum() + 5,
                'recap_prix' => true,
                'prix_base' => $prixBase,
                'prix_final' => $commande->getPrixMenu(),
                'reduction_appliquee' => $reductionAppliquee,
            ]);
        }

        // Vérifier si réduction applicable pour l'affichage
        $seuilReduction = $menu->getNombrePersonneMinimum() + 5;

        return $this->render('commande/new.html.twig', [
            'menu' => $menu,
            'menus' => null,
            'form' => $form,
            'seuil_reduction' => $seuilReduction,
        ]);
    }

    #[Route('/{numeroCommande}/annuler', name: 'app_commande_annuler', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function annuler(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])]
        Commande $commande,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande.');
        }

        if ($commande->getStatut() !== Commande::STATUT_EN_ATTENTE) {
            $this->addFlash('danger', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_profile');
        }

        if (!$this->isCsrfTokenValid('annuler_' . $commande->getNumeroCommande(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_profile');
        }

        $commande->setStatut(Commande::STATUT_ANNULEE);

        // Restituer le stock du menu
        $menu = $commande->getMenu();
        $menu->setQuantiteRestante($menu->getQuantiteRestante() + 1);

        $em->flush();

        $this->addFlash('success', 'Votre commande a été annulée.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{numeroCommande}', name: 'app_commande_show', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])]
        Commande $commande,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Sécurité : seul le propriétaire ou un salarié/admin peut voir
        if (
            $commande->getUtilisateur() !== $this->getUser()
            && !$this->isGranted('ROLE_SALARIE')
        ) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette commande.');
        }

        $editable = $commande->getStatut() === Commande::STATUT_EN_ATTENTE;
        $editForm = null;

        if ($editable) {
            $menu = $commande->getMenu();
            $editForm = $this->createForm(EditCommandeFormType::class, $commande, [
                'nombre_personne_min' => $menu->getNombrePersonneMinimum(),
            ]);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                // Validation nombrePersonne >= minimum
                if ($commande->getNombrePersonne() < $menu->getNombrePersonneMinimum()) {
                    $this->addFlash('danger', sprintf(
                        'Le nombre de personnes doit être d\'au moins %d pour ce menu.',
                        $menu->getNombrePersonneMinimum()
                    ));
                } else {
                    // Recalculer le prix si le nombre de personnes a changé
                    $commande->calculerPrixMenu();
                    $em->flush();

                    $this->addFlash('success', 'La commande a été mise à jour.');
                    return $this->redirectToRoute('app_commande_show', [
                        'numeroCommande' => $commande->getNumeroCommande(),
                    ]);
                }
            }
        }

        // Vérifier si la réduction a été appliquée
        $menu = $commande->getMenu();
        $prixSansReduction = $menu->getPrixParPersonne() * $commande->getNombrePersonne();
        $reductionAppliquee = $commande->getPrixMenu() < $prixSansReduction;

        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'reduction_appliquee' => $reductionAppliquee,
            'prix_sans_reduction' => $prixSansReduction,
            'edit_form' => $editForm?->createView(),
            'editable' => $editable,
        ]);
    }
}
