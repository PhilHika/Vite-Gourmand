<?php

namespace App\Controller;

use App\Document\ContenuSite;
use App\Document\Horaire;
use App\Entity\Commande;
use App\Entity\Avis;
use App\Form\ProfileFormType;
use App\Repository\CommandeRepository;
use App\Repository\ContenuSiteRepository;
use App\Repository\HoraireRepository;
use App\Repository\AvisRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();

        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_profile');
        }

        $commandes = $commandeRepository->findBy(
            ['utilisateur' => $user],
            ['dateCommande' => 'DESC']
        );

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'commandes' => $commandes,
            'profile_form' => $profileForm,
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

        return $this->render('profile/horaires.html.twig', [
            'jours' => $jours,
            'horaires' => $horairesExistants,
            'errors' => $errors,
            'formData' => $formData,
        ]);
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
     * @param array<string, Horaire>                                     $horairesExistants
     */
    private function applyHoraires(
        DocumentManager $dm,
        array $formData,
        array $horairesExistants
    ): void {
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

    #[Route('/profil/gestion-site', name: 'app_profile_gestion_site', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SALARIE')]
    public function gestionSite(
        Request $request,
        HoraireRepository $horaireRepository,
        ContenuSiteRepository $contenuSiteRepository,
        DocumentManager $dm,
        AvisRepository $avisRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $contenu = $request->request->get('contenu', '');

            $clesAutorisees = ['description', 'conditions_vente'];
            if (!in_array($type, $clesAutorisees, true)) {
                $this->addFlash('danger', 'Type de contenu invalide.');

                return $this->redirectToRoute('app_profile_gestion_site');
            }

            $document = $contenuSiteRepository->findByCle($type);
            if (!$document) {
                $document = new ContenuSite();
                $document->setCle($type);
                $dm->persist($document);
            }
            $document->setContenu($contenu);
            $dm->flush();

            $label = $type === 'description'
                ? 'Description'
                : 'Conditions de vente';
            $this->addFlash('success', $label . ' mise à jour avec succès.');

            return $this->redirectToRoute('app_profile_gestion_site');
        }

        $horairesExistants = [];
        foreach ($horaireRepository->findAll() as $horaire) {
            $horairesExistants[$horaire->getJour()] = $horaire;
        }

        $description = $contenuSiteRepository->findByCle('description');
        $conditionsVente = $contenuSiteRepository->findByCle('conditions_vente');

        // Récupère les avis en attente et l'historique
        $avisEnAttente = $avisRepository->findBy(['statut' => Avis::STATUT_EN_ATTENTE], ['id' => 'DESC']);
        $historiqueAvis = $avisRepository->findBy([], ['id' => 'DESC']);

        return $this->render('profile/gestion_site.html.twig', [
            'horaires' => $horairesExistants,
            'description' => $description?->getContenu(),
            'conditions_vente' => $conditionsVente?->getContenu(),
            'avis_en_attente' => $avisEnAttente,
            'historique_avis' => $historiqueAvis,
        ]);
    }

    #[Route('/profil/gestion-site/avis/{numeroCommande}', name: 'app_profile_gerer_avis', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SALARIE')]
    public function gererAvis(
        #[MapEntity(mapping: ['numeroCommande' => 'numeroCommande'])] Commande $commande,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $avis = $commande->getAvis();

        if (!$avis) {
            $this->addFlash('warning', 'Aucun avis n\'a encore été laissé pour cette commande.');
            return $this->redirectToRoute('app_profile_gestion_site');
        }

        $form = $this->createForm(\App\Form\AvisStatutFormType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le statut de l\'avis a été mis à jour.');
            return $this->redirectToRoute('app_profile_gestion_site');
        }

        return $this->render('profile/gerer_avis.html.twig', [
            'commande' => $commande,
            'avis' => $avis,
            'form' => $form->createView()
        ]);
    }
}
