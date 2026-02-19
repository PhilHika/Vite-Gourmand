<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
class AdminUserController extends AbstractController
{
    private function checkAdminAccess(): ?Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous n\'avez pas les droits nécessaire pour acceder.');
            return $this->redirectToRoute('app_home');
        }
        return null;
    }

    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        if ($redirect = $this->checkAdminAccess()) {
            return $redirect;
        }

        // Récupérer tous les utilisateurs sauf celui actuellement connecté
        $currentUser = $this->getUser();
        $allUsers = $utilisateurRepository->findAll();
        $users = array_filter($allUsers, function ($user) use ($currentUser) {
            return $user !== $currentUser;
        });

        return $this->render('admin_user/index.html.twig', [
            'utilisateurs' => $users,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Utilisateur $utilisateur, EntityManagerInterface $entityManager): Response
    {
        if ($redirect = $this->checkAdminAccess()) {
            return $redirect;
        }

        // Empêcher l'édition de ses propres droits
        if ($utilisateur === $this->getUser()) {
            $this->addFlash('warning', 'Vous ne pouvez pas modifier vos propres droits.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        // Formulaire de modification de rôle
        $form = $this->createFormBuilder($utilisateur)
            ->add('role', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'libelle',
                'label' => 'Rôle',
                'placeholder' => 'Choisir un rôle',
                'attr' => ['class' => 'form-select']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le rôle de l\'utilisateur a été mis à jour avec succès.');

            return $this->redirectToRoute('app_admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_user/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }
}
