<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Form\PlatFormType;
use App\Repository\PlatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/plat')]
class AdminPlatController extends AbstractController
{
    #[Route('/new', name: 'app_admin_plat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PlatRepository $platRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé. Vous n\'avez pas les droits pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        $plat = new Plat();
        $form = $this->createForm(PlatFormType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $fileContent = file_get_contents($photoFile->getPathname());
                $plat->setPhoto($fileContent);
            }

            $platRepository->save($plat, true);

            $this->addFlash('success', 'Le plat a bien été créé.');

            return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
        }

        return $this->render('admin_plat/plat_edit.html.twig', [
            'plat' => $plat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_plat_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Plat $plat, PlatRepository $platRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé. Vous n\'avez pas les droits pour accéder à cette page.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(PlatFormType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Deletion
            if ($form->get('deletePhoto')->getData()) {
                $plat->setPhoto(null);
            }

            // Handle Upload (Overwrites deletion if both selected, or we can prioritize)
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $fileContent = file_get_contents($photoFile->getPathname());
                $plat->setPhoto($fileContent);
            }

            $platRepository->save($plat, true);

            $this->addFlash('success', 'Le plat a bien été modifié.');

            return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
        }

        return $this->render('admin_plat/plat_edit.html.twig', [
            'plat' => $plat,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/image', name: 'app_admin_plat_image', methods: ['GET'])]
    public function image(Plat $plat): Response
    {
        $photoContent = $plat->getPhoto();

        if (!$photoContent) {
            throw $this->createNotFoundException('Image non trouvée');
        }

        if (is_resource($photoContent)) {
            $photoContent = stream_get_contents($photoContent);
        }

        $response = new Response($photoContent);

        // Assuming JPEG or PNG based on common web usage.
        // `finfo` is better.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($photoContent) ?: 'image/jpeg';

        $response->headers->set('Content-Type', $mimeType);

        // Caching
        $response->setPublic();
        $response->setMaxAge(3600); // 1 hour cache
        $response->setEtag(md5($photoContent)); // Simple ETag based on content usage

        return $response;
    }

    #[Route('/{id}/delete-photo', name: 'app_admin_plat_delete_photo', methods: ['POST'])]
    public function deletePhoto(Request $request, Plat $plat, PlatRepository $platRepository): Response
    {
        if (!$this->isGranted('ROLE_SALARIE')) {
            $this->addFlash('danger', 'Accès refusé.');
            return $this->redirectToRoute('app_home');
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete-photo-' . $plat->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
        }

        $plat->setPhoto(null);
        $platRepository->save($plat, true);

        $this->addFlash('success', 'La photo a bien été supprimée.');

        return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
    }
}
