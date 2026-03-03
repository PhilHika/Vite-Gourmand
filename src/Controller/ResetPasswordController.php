<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\ResetPasswordRequestRepository;
use App\Service\PasswordResetMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ResetPasswordController extends AbstractController
{
    /**
     * Étape 1 : L'utilisateur saisit son email pour demander un lien de reset.
     */
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        EntityManagerInterface $entityManager,
        ResetPasswordRequestRepository $resetRepo,
        PasswordResetMailerService $mailerService,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $entityManager->getRepository(\App\Entity\Utilisateur::class)
                ->findOneBy(['email' => $email]);

            if ($user) {
                // Supprimer les anciens tokens pour cet utilisateur
                $resetRepo->removeTokensForUser($user);

                // Générer un nouveau token
                $token = Uuid::v4()->toRfc4122();
                $expiresAt = new \DateTimeImmutable('+1 hour');

                $resetRequest = new ResetPasswordRequest($user, $token, $expiresAt);
                $entityManager->persist($resetRequest);
                $entityManager->flush();

                // Envoyer l'email
                $mailerService->envoyerLienReset($user, $token);
            }

            // Message identique que l'email existe ou non (sécurité)
            $this->addFlash(
                'success',
                'Si cette adresse email est associée à un compte, '
                    . 'un lien de réinitialisation vous a été envoyé.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Étape 2 : L'utilisateur clique le lien et saisit son nouveau mot de passe.
     */
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordRequestRepository $resetRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $resetRequest = $resetRepo->findValidToken($token);

        if (!$resetRequest) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $resetRequest->getUtilisateur();

            // Hasher et mettre à jour le mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Supprimer le token utilisé
            $entityManager->remove($resetRequest);
            $entityManager->flush();

            // Invalider la session pour forcer une nouvelle connexion
            // Sécurité : l'utilisateur doit prouver qu'il connait le nouveau mot de passe
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $this->addFlash(
                'success',
                'Votre mot de passe a été réinitialisé avec succès. '
                    . 'Veuillez vous connecter avec votre nouveau mot de passe.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }
}
