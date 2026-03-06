<?php

namespace App\Controller;

use App\Document\Horaire;
use App\Repository\CommandeRepository;
use App\Repository\HoraireRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile')]
    public function index(CommandeRepository $commandeRepository): Response
    {
        $commandes = $commandeRepository->findBy(
            ['utilisateur' => $this->getUser()],
            ['dateCommande' => 'DESC']
        );

        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
            'commandes' => $commandes,
        ]);
    }

    #[Route('/profil/horaires', name: 'app_profile_horaires', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SALARIE')]
    public function editHoraires(
        Request $request,
        HoraireRepository $horaireRepository,
        DocumentManager $dm
    ): Response {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        $timePattern = '/^[0-2][0-9]:[0-5][0-9]$/';

        $horairesExistants = [];
        foreach ($horaireRepository->findAll() as $horaire) {
            $horairesExistants[$horaire->getJour()] = $horaire;
        }

        $errors = [];
        $formData = [];

        if ($request->isMethod('POST')) {
            $formData = $this->extractFormData($request, $jours);
            $errors = $this->validateHoraires($formData, $timePattern);

            if (!empty($errors)) {
                $this->addFlash('danger', 'Certains horaires sont invalides. Merci de respecter strictement le format HH:mm (ex : 09:00).');
            } else {
                $this->applyHoraires($dm, $formData, $horairesExistants);
                $dm->flush();
                $this->addFlash('success', 'Horaires mis à jour avec succès.');
                return $this->redirectToRoute('app_profile_horaires');
            }
        }

        $response = new Response('', empty($errors) ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);

        return $this->render('profile/horaires.html.twig', [
            'jours' => $jours,
            'horaires' => $horairesExistants,
            'errors' => $errors,
            'formData' => $formData,
        ], $response);
    }

    /**
     * Extrait les données du formulaire pour chaque jour.
     *
     * @param string[] $jours
     * @return array<string, array{ouverture: string, fermeture: string}>
     */
    private function extractFormData(Request $request, array $jours): array
    {
        $formData = [];
        foreach ($jours as $jour) {
            $key = strtolower($jour);
            $formData[$jour] = [
                'ouverture' => trim($request->request->get('ouverture_' . $key, '')),
                'fermeture' => trim($request->request->get('fermeture_' . $key, '')),
            ];
        }

        return $formData;
    }

    /**
     * Valide les données du formulaire et retourne les erreurs par jour.
     *
     * @param array<string, array{ouverture: string, fermeture: string}> $formData
     * @return array<string, string>
     */
    private function validateHoraires(array $formData, string $timePattern): array
    {
        $errors = [];

        foreach ($formData as $jour => $heures) {
            $ouverture = $heures['ouverture'];
            $fermeture = $heures['fermeture'];

            if ($ouverture === '' && $fermeture === '') {
                continue;
            }

            if ($ouverture === '' || $fermeture === '') {
                $errors[$jour] = 'Les deux heures doivent être renseignées ou toutes les deux vides (jour fermé)';
                continue;
            }

            if (!preg_match($timePattern, $ouverture)) {
                $errors[$jour] = 'Format heure ouverture invalide (attendu HH:mm, ex: 09:00)';
                continue;
            }

            if (!preg_match($timePattern, $fermeture)) {
                $errors[$jour] = 'Format heure fermeture invalide (attendu HH:mm, ex: 18:30)';
                continue;
            }

            if ($fermeture <= $ouverture) {
                $errors[$jour] = 'L\'heure de fermeture doit être après l\'heure d\'ouverture';
            }
        }

        return $errors;
    }

    /**
     * Applique les modifications MongoDB (persist/remove) sans flush.
     *
     * @param array<string, array{ouverture: string, fermeture: string}> $formData
     * @param array<string, Horaire> $horairesExistants
     */
    private function applyHoraires(DocumentManager $dm, array $formData, array $horairesExistants): void
    {
        foreach ($formData as $jour => $heures) {
            $ouverture = $heures['ouverture'];
            $fermeture = $heures['fermeture'];
            $horaireExistant = $horairesExistants[$jour] ?? null;

            if ($ouverture === '' && $fermeture === '') {
                if ($horaireExistant) {
                    $dm->remove($horaireExistant);
                }
            } elseif ($horaireExistant) {
                $horaireExistant->setHeureOuverture($ouverture);
                $horaireExistant->setHeureFermeture($fermeture);
            } else {
                $horaire = new Horaire();
                $horaire->setJour($jour);
                $horaire->setHeureOuverture($ouverture);
                $horaire->setHeureFermeture($fermeture);
                $dm->persist($horaire);
            }
        }
    }
}
