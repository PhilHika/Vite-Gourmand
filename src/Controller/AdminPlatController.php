<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Form\PlatFormType;
use App\Repository\PlatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/plat')]
#[IsGranted('ROLE_SALARIE')]
class AdminPlatController extends AbstractController
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/plats')]
        private readonly string $uploadDir,
    ) {
    }

    #[Route('/', name: 'app_admin_plat_index', methods: ['GET'])]
    public function index(PlatRepository $platRepository): Response
    {
        return $this->render('admin_plat/index.html.twig', [
            'plats' => $platRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_plat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PlatRepository $platRepository): Response
    {
        $plat = new Plat();
        $form = $this->createForm(PlatFormType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $newFilename = $this->handleUpload($photoFile);
                $plat->setPhoto($newFilename);
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
        $form = $this->createForm(PlatFormType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle Deletion
            if ($form->get('deletePhoto')->getData()) {
                $this->removePhotoFile($plat->getPhoto());
                $plat->setPhoto(null);
            }

            // Handle Upload (Overwrites deletion if both selected)
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                // Remove old file first
                $this->removePhotoFile($plat->getPhoto());

                $newFilename = $this->handleUpload($photoFile);
                $plat->setPhoto($newFilename);
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

    #[Route('/{id}/delete-photo', name: 'app_admin_plat_delete_photo', methods: ['POST'])]
    public function deletePhoto(Request $request, Plat $plat, PlatRepository $platRepository): Response
    {
        // Validate CSRF token
        if (!$this->isCsrfTokenValid('delete-photo-' . $plat->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
        }

        $this->removePhotoFile($plat->getPhoto());
        $plat->setPhoto(null);
        $platRepository->save($plat, true);

        $this->addFlash('success', 'La photo a bien été supprimée.');

        return $this->redirectToRoute('app_admin_plat_edit', ['id' => $plat->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_admin_plat_delete', methods: ['POST'])]
    public function delete(Request $request, Plat $plat, PlatRepository $platRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete-plat-' . $plat->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_plat_index');
        }

        $titre = $plat->getTitrePlat();

        // Remove photo file before deleting the entity
        $this->removePhotoFile($plat->getPhoto());

        $platRepository->remove($plat, true);

        $this->addFlash('success', 'Le plat "' . $titre . '" a bien été supprimé.');

        return $this->redirectToRoute('app_admin_plat_index');
    }

    /**
     * Upload a photo file and return the generated filename.
     */
    private function handleUpload(\Symfony\Component\HttpFoundation\File\UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower();
        $newFilename = uniqid() . '_' . $safeFilename . '.' . $file->guessExtension();

        $file->move($this->uploadDir, $newFilename);

        return $newFilename;
    }

    /**
     * Remove a photo file from disk if it exists.
     */
    private function removePhotoFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $filePath = $this->uploadDir . '/' . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
