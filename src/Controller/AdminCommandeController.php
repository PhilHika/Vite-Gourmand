<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\AdminCommandeFormType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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

    #[Route('/{numeroCommande}/edit', name: 'app_admin_commande_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])]
        Commande $commande,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
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
                // Détecter si le statut a changé
                $nouveauStatut = $commande->getStatut();
                $em->flush();

                // Envoi email si le statut a changé
                if ($ancienStatut !== $nouveauStatut) {
                    $user = $commande->getUtilisateur();
                    $statutLabels = [
                        Commande::STATUT_EN_ATTENTE => 'En attente',
                        Commande::STATUT_CONFIRMEE => 'Confirmée',
                        Commande::STATUT_EN_PREPARATION => 'En préparation',
                        Commande::STATUT_LIVREE => 'Livrée',
                        Commande::STATUT_ANNULEE => 'Annulée',
                    ];
                    $labelStatut = $statutLabels[$nouveauStatut] ?? $nouveauStatut;

                    $emailMessage = (new Email())
                        ->from('noreply@vite-et-gourmand.fr')
                        ->to($user->getEmail())
                        ->subject('Commande ' . $commande->getNumeroCommande() . ' — Statut mis à jour')
                        ->html(sprintf(
                            '<h2>Mise à jour de votre commande</h2>
                            <p>Bonjour <strong>%s</strong>,</p>
                            <p>Le statut de votre commande <strong>%s</strong> a été mis à jour :</p>
                            <p style="font-size: 1.2em;">Nouveau statut : <strong>%s</strong></p>
                            <h3>Rappel de votre commande</h3>
                            <ul>
                                <li><strong>Menu :</strong> %s</li>
                                <li><strong>Date de prestation :</strong> %s</li>
                                <li><strong>Heure :</strong> %s</li>
                            </ul>
                            <p>Cordialement,<br>L\'\u00e9quipe Vite &amp; Gourmand</p>',
                            htmlspecialchars($user->getPrenom()),
                            htmlspecialchars($commande->getNumeroCommande()),
                            htmlspecialchars($labelStatut),
                            htmlspecialchars($menu->getTitre()),
                            $commande->getDatePrestation()->format('d/m/Y'),
                            htmlspecialchars($commande->getHeureLivraison())
                        ));
                    $mailer->send($emailMessage);
                }

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
