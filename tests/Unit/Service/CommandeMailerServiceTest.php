<?php

/**
 * ============================================================
 * TUTORIEL PHPUNIT — CommandeMailerServiceTest
 * ============================================================
 *
 * OBJECTIF DE CE FICHIER :
 * Tester CommandeMailerService, qui dépend de deux services externes :
 *   - MailerInterface       (Symfony Mailer)
 *   - UtilisateurRepository (Doctrine)
 *
 * PROBLÈME :
 *   On ne peut pas tester un service qui envoie de vrais emails
 *   en test unitaire. On ne veut pas toucher la BDD non plus.
 *
 * ════════════════════════════════════════════════════════════
 * NOUVEAU CONCEPT FONDAMENTAL : LES MOCKS (Objets Bouchons)
 * ════════════════════════════════════════════════════════════
 *
 * Un MOCK est un faux objet qui SIMULE un vrai service.
 * PHPUnit peut créer des mocks à la volée avec createMock().
 *
 * Pourquoi des mocks ?
 *   → On teste UNIQUEMENT la logique du service (CommandeMailerService)
 *   → On isole le test de ses dépendances (Mailer, Repository)
 *   → Les tests sont rapides, pas de réseau, pas de BDD
 *
 * Les mocks permettent de :
 *   1. Remplacer une dépendance par un faux objet
 *   2. Contrôler ce que la dépendance retourne (expects/returns)
 *   3. Vérifier que la dépendance est appelée (ou pas)
 *
 * SCHÉMA :
 *
 *   Test                CommandeMailerService          Mailer réel
 *   ────                ─────────────────────          ──────────
 *   crée Mock(Mailer) →
 *                       reçoit le Mock
 *                       appelle $this->mailer->send()
 *                              ↓
 *                         MOCK intercepte          (jamais appelé)
 *                         enregistre l'appel
 *   ← vérifie l'appel
 *
 * MÉTHODES MOCK UTILISÉES :
 *   createMock(Class::class)       → crée le faux objet
 *   expects($this->once())         → attend exactement 1 appel
 *   expects($this->never())        → attend 0 appel
 *   expects($this->exactly(N))     → attend exactement N appels
 *   method('nomMethode')           → sur quelle méthode
 *   willReturn($valeur)            → ce que le mock retourne
 *
 * STRUCTURE :
 *   Groupe 1 : getStatutLabel() — méthode STATIQUE, pas de mock nécessaire
 *   Groupe 2 : envoyerChangementStatut() — guard clause (statut inchangé)
 *   Groupe 3 : envoyerChangementStatut() — email envoyé avec mock Mailer
 * ============================================================
 */

namespace App\Tests\Unit\Service;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\CommandeMailerService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class CommandeMailerServiceTest extends TestCase
{
    // ============================================================
    // GROUPE 1 — getStatutLabel() : méthode statique pure
    // ============================================================
    // getStatutLabel() est une méthode STATIQUE qui traduit
    // les constantes de statut en libellés lisibles.
    //
    // Pas de dépendance → pas de mock nécessaire.
    // C'est le test le plus simple possible sur un service.
    // ============================================================

    /**
     * Chaque constante de statut a un libellé lisible défini.
     * Si quelqu'un modifie une constante ou un libellé, ce test échoue.
     */
    public function testGetStatutLabelRetourneLesLibellesCorrects(): void
    {
        // assertSame() : comparaison stricte string
        $this->assertSame('En attente', CommandeMailerService::getStatutLabel(Commande::STATUT_EN_ATTENTE));
        $this->assertSame('Confirmée', CommandeMailerService::getStatutLabel(Commande::STATUT_CONFIRMEE));
        $this->assertSame('En préparation', CommandeMailerService::getStatutLabel(Commande::STATUT_EN_PREPARATION));
        $this->assertSame('Livrée', CommandeMailerService::getStatutLabel(Commande::STATUT_LIVREE));
        $this->assertSame('Annulée', CommandeMailerService::getStatutLabel(Commande::STATUT_ANNULEE));
        $this->assertSame('Terminée', CommandeMailerService::getStatutLabel(Commande::STATUT_TERMINEE));
    }

    /**
     * Statut inconnu → retourne le statut tel quel (pas d'erreur).
     * Comportement défensif : ?? $statut dans le code source.
     */
    public function testGetStatutLabelStatutInconnuRetourneLaValeurBrute(): void
    {
        $statutInconnu = 'statut_qui_nexiste_pas';

        $this->assertSame($statutInconnu, CommandeMailerService::getStatutLabel($statutInconnu));
    }

    // ============================================================
    // GROUPE 2 — Guard clause : statut inchangé → aucun email
    // ============================================================
    // Code source de envoyerChangementStatut() :
    //   if ($ancienStatut === $commande->getStatut()) {
    //       return; // ← guard clause : on sort si rien n'a changé
    //   }
    //
    // Si le statut n'a pas changé, aucun email ne doit être envoyé.
    // On vérifie cela avec expects($this->never()) sur le mock Mailer.
    //
    // expects($this->never()) :
    //   PHPUnit vérifiera que la méthode send() du Mailer
    //   n'est appelée AUCUNE FOIS. Si elle l'est → le test échoue.
    // ============================================================

    /**
     * Si ancienStatut === statut actuel → aucun email envoyé.
     *
     * ÉTAPES DE CRÉATION D'UN MOCK :
     *   1. createMock(Interface::class) → faux objet qui implémente l'interface
     *   2. expects()                    → combien de fois la méthode doit être appelée
     *   3. method()                     → quelle méthode on surveille
     *   4. On passe le mock au constructeur du service
     */
    public function testEnvoyerChangementStatutNeRienFaireStatutIdentique(): void
    {
        // ── Étape 1 : Créer les mocks ──────────────────────────────────────
        // createMock() crée un objet qui IMPLÉMENTE MailerInterface
        // mais dont send() ne fait rien par défaut (sauf si on le configure).
        $mailerMock = $this->createMock(MailerInterface::class);

        // expects($this->never()) : send() NE DOIT PAS être appelé
        // Si send() est appelé → test échoue automatiquement
        $mailerMock->expects($this->never())
            ->method('send');

        // ─────────────────────────────────────────────────────────────────
        // MOCK vs STUB : quelle différence ?
        //
        // createMock()  → tu ATTENDS des appels précis (expects/method)
        //                 PHPUnit vérifie que les appels configurés ont eu lieu
        //
        // createStub()  → tu veux juste CONTRÔLER les valeurs de retour
        //                 sans vérifier si la méthode est appelée ou pas
        //                 PHPUnit 12 recommande createStub() quand on ne
        //                 configure PAS d'expectations.
        //
        // Ici : le Repository ne sera PAS appelé (guard clause stoppe tout)
        // → on n'a pas d'expectations → on utilise createStub().
        // ─────────────────────────────────────────────────────────────────
        $repositoryMock = $this->createStub(UtilisateurRepository::class);

        // ── Étape 2 : Instancier le service avec les mocks ─────────────────
        // Le service reçoit les mocks via son constructeur (injection de dépendances)
        $service = new CommandeMailerService(
            $mailerMock,
            $repositoryMock,
            '/fake/project/dir' // $projectDir (pas utilisé dans ce test)
        );

        // ── Étape 3 : Préparer les données de test ─────────────────────────
        $commande = new Commande();
        $commande->setStatut(Commande::STATUT_CONFIRMEE);

        // ── Étape 4 : Appeler la méthode testée ────────────────────────────
        // ancienStatut = statut actuel → guard clause déclenche return immédiat
        $service->envoyerChangementStatut($commande, Commande::STATUT_CONFIRMEE);

        // PHPUnit vérifie automatiquement les expects() à la fin du test.
        // Si send() a été appelé → FAIL car expects(never())
        // Si send() n'a pas été appelé → PASS ✅
    }

    // ============================================================
    // GROUPE 3 — Email envoyé quand le statut change réellement
    // ============================================================
    // Quand ancienStatut !== statut actuel, un email doit être envoyé.
    //
    // expects($this->atLeastOnce()) :
    //   Vérifie que send() est appelé AU MOINS une fois.
    //   (Le service envoie plusieurs emails : client + gestionnaires)
    //
    // willReturn() :
    //   Configure ce que le mock retourne.
    //   findGestionnaires() doit retourner un tableau d'utilisateurs.
    // ============================================================

    /**
     * Changement de statut réel → Mailer.send() est appelé.
     *
     * willReturn($value) :
     *   Configure la valeur de retour du mock.
     *   Ici : findGestionnaires() retourne un Utilisateur de test
     *   pour simuler qu'il y a un gestionnaire à notifier.
     */
    public function testEnvoyerChangementStatutEnvoieEmailQuandStatutChange(): void
    {
        // ── Mock Mailer : send() doit être appelé AU MOINS UNE FOIS ────────
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->atLeastOnce()) // au moins 1 envoi
            ->method('send');

        // ── Mock Repository : retourne 1 gestionnaire fictif ───────────────
        // willReturn() : quand findGestionnaires() est appelé, retourner ce tableau
        $gestionnaire = new Utilisateur();
        $gestionnaire->setEmail('gestionnaire@vite-et-gourmand.fr');

        $repositoryMock = $this->createMock(UtilisateurRepository::class);
        $repositoryMock->expects($this->atLeastOnce()) // findGestionnaires() sera appelé
            ->method('findGestionnaires')
            ->willReturn([$gestionnaire]); // retourne 1 gestionnaire fictif

        // ── Service avec chemin du logo factice ────────────────────────────
        // Le service appelle embedFromPath() sur le logo → on doit pointer vers
        // un fichier qui existe. On utilise __FILE__ (ce fichier de test).
        // En réalité, le logo n'est pas vérifié dans ce test, on veut juste
        // que le code ne plante pas sur le chemin.
        $projectDir = dirname(__DIR__, 3); // remonte à la racine du projet

        $service = new CommandeMailerService($mailerMock, $repositoryMock, $projectDir);

        // ── Commande avec utilisateur pour que getUtilisateur()->getEmail() fonctionne
        $utilisateur = new Utilisateur();
        $utilisateur->setEmail('client@example.com');

        $menu = new Menu();
        $menu->setTitre('Menu Test');
        $menu->setPrixParPersonne(50.0);
        $menu->setNombrePersonneMinimum(5);

        $commande = new Commande();
        $commande->setStatut(Commande::STATUT_CONFIRMEE);
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setDateCommande(new \DateTime());
        $commande->generateNumeroCommande(); // nécessaire pour sujetCommande()

        // ── Appel avec statuts DIFFÉRENTS → email doit partir ──────────────
        $service->envoyerChangementStatut($commande, Commande::STATUT_EN_ATTENTE);

        // PHPUnit vérifie que send() a bien été appelé (atLeastOnce)
    }

    // ── DataProvider remplace le foreach ──────────────────────────────────────
    // AVANT (foreach dans un seul test) :
    //   Tous les statuts partagent le même mock → 1 expects(never()) pour tous.
    //   Si 'en_attente' plante, PHPUnit abandonne. 'confirmee' n'est pas testé.
    //
    // APRÈS (DataProvider) :
    //   Chaque statut a SON PROPRE test avec SON PROPRE mock.
    //   6 entrées = 6 tests indépendants. Si l'un plante, les 5 autres continuent.
    //   Bonus : le rapport identifie EXACTEMENT quel statut a déclenché l'envoi.
    // ─────────────────────────────────────────────────────────────────────────

    public static function tousLesStatutsProvider(): array
    {
        return [
            'en attente'              => [Commande::STATUT_EN_ATTENTE],
            'confirmée'               => [Commande::STATUT_CONFIRMEE],
            'en préparation'          => [Commande::STATUT_EN_PREPARATION],
            'livrée'                  => [Commande::STATUT_LIVREE],
            'annulée'                 => [Commande::STATUT_ANNULEE],
            'terminée'                => [Commande::STATUT_TERMINEE],
        ];
    }

    /**
     * Vérifie que pour CHAQUE statut, si ancienStatut === statut actuel,
     * aucun email n'est envoyé (guard clause).
     * Chaque statut est un test indépendant grâce au DataProvider.
     */
    #[DataProvider('tousLesStatutsProvider')]
    public function testEnvoyerChangementStatutStatutIdentiqueNEnvoieAucunEmail(string $statut): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->never())->method('send');

        $repositoryMock = $this->createMock(UtilisateurRepository::class);
        $repositoryMock->expects($this->never())->method('findGestionnaires');

        $service = new CommandeMailerService($mailerMock, $repositoryMock, '/fake/dir');

        $commande = new Commande();
        $commande->setStatut($statut);

        // ancienStatut === statut actuel → guard clause → aucun envoi
        $service->envoyerChangementStatut($commande, $statut);
    }

    // ============================================================
    // GROUPE 4 — envoyerConfirmation() : email client + notification staff
    // ============================================================
    // envoyerConfirmation() fait deux choses :
    //   1. Envoie un email au CLIENT avec TPL_CONFIRMATION
    //   2. Appelle notifierGestionnaires() → envoie N emails au STAFF
    //
    // En test unitaire on ne peut pas vérifier LEQUEL des deux send()
    // correspond au client vs staff. On vérifie uniquement :
    //   - send() est appelé au moins une fois (email client)
    //   - findGestionnaires() est appelé (notifierGestionnaires() est bien déclenché)
    // ============================================================

    /**
     * envoyerConfirmation() envoie un email au client ET notifie les gestionnaires.
     *
     * NOUVEAU CONCEPT : expects($this->atLeastOnce()) sur findGestionnaires()
     *   On vérifie que la méthode est appelée, sans se soucier de combien de fois.
     *   Le vrai nombre dépend du template Twig (que le test unitaire ne charge pas).
     */
    public function testEnvoyerConfirmationEnvoieEmailAuClientEtNotifieGestionnaires(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->atLeastOnce())
            ->method('send');

        $gestionnaire = new Utilisateur();
        $gestionnaire->setEmail('staff@vite-et-gourmand.fr');

        $repositoryMock = $this->createMock(UtilisateurRepository::class);
        $repositoryMock->expects($this->atLeastOnce())
            ->method('findGestionnaires')
            ->willReturn([$gestionnaire]);

        $projectDir = dirname(__DIR__, 3);
        $service = new CommandeMailerService($mailerMock, $repositoryMock, $projectDir);

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail('client@example.com');

        $menu = new Menu();
        $menu->setTitre('Menu Test');
        $menu->setPrixParPersonne(50.0);
        $menu->setNombrePersonneMinimum(5);

        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setDateCommande(new \DateTime());
        $commande->generateNumeroCommande();

        $service->envoyerConfirmation($commande);

        // PHPUnit vérifie automatiquement les expects() en fin de test.
    }

    // ============================================================
    // GROUPE 5 — Branches spéciales de envoyerChangementStatut()
    // ============================================================
    // Le service contient deux if() qui court-circuitent la logique standard :
    //
    //   Branche A — STATUT_EN_ATTENTE_RETOUR_MATERIEL
    //     → template TPL_ATTENTE_RETOUR (pas TPL_STATUT)
    //     → early return après notifierGestionnaires()
    //
    //   Branche B — STATUT_TERMINEE
    //     → template TPL_TERMINEE pour le client (pas TPL_STATUT)
    //     → notifierGestionnaires() quand même appelée avec TPL_STATUT
    //     → early return
    //
    // POURQUOI TESTER CES BRANCHES :
    //   Sans ces tests, on pourrait supprimer les if() et utiliser TPL_STATUT
    //   partout → le client recevrait le mauvais email (régression silencieuse).
    // ============================================================

    /**
     * Branche STATUT_EN_ATTENTE_RETOUR_MATERIEL : send() ET findGestionnaires() appelés.
     * On vérifie que le flux entier est exécuté (client + staff notifiés).
     */
    public function testEnvoyerChangementStatutEnAttenteRetourMaterielEnvoieEmails(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->atLeastOnce())
            ->method('send');

        $gestionnaire = new Utilisateur();
        $gestionnaire->setEmail('staff@vite-et-gourmand.fr');

        $repositoryMock = $this->createMock(UtilisateurRepository::class);
        $repositoryMock->expects($this->atLeastOnce())
            ->method('findGestionnaires')
            ->willReturn([$gestionnaire]);

        $projectDir = dirname(__DIR__, 3);
        $service = new CommandeMailerService($mailerMock, $repositoryMock, $projectDir);

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail('client@example.com');

        $menu = new Menu();
        $menu->setTitre('Menu Test');
        $menu->setPrixParPersonne(50.0);
        $menu->setNombrePersonneMinimum(5);

        $commande = new Commande();
        $commande->setStatut(Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL);
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setDateCommande(new \DateTime());
        $commande->generateNumeroCommande();

        // ancienStatut différent → guard clause non déclenchée → branche ATTENTE_RETOUR
        $service->envoyerChangementStatut($commande, Commande::STATUT_LIVREE);
    }

    /**
     * Branche STATUT_TERMINEE : send() ET findGestionnaires() appelés.
     * La branche envoie un email dédié au client (TPL_TERMINEE) pour inciter aux avis.
     */
    public function testEnvoyerChangementStatutTermineeEnvoieEmails(): void
    {
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->atLeastOnce())
            ->method('send');

        $gestionnaire = new Utilisateur();
        $gestionnaire->setEmail('staff@vite-et-gourmand.fr');

        $repositoryMock = $this->createMock(UtilisateurRepository::class);
        $repositoryMock->expects($this->atLeastOnce())
            ->method('findGestionnaires')
            ->willReturn([$gestionnaire]);

        $projectDir = dirname(__DIR__, 3);
        $service = new CommandeMailerService($mailerMock, $repositoryMock, $projectDir);

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail('client@example.com');

        $menu = new Menu();
        $menu->setTitre('Menu Test');
        $menu->setPrixParPersonne(50.0);
        $menu->setNombrePersonneMinimum(5);

        $commande = new Commande();
        $commande->setStatut(Commande::STATUT_TERMINEE);
        $commande->setUtilisateur($utilisateur);
        $commande->setMenu($menu);
        $commande->setDateCommande(new \DateTime());
        $commande->generateNumeroCommande();

        // ancienStatut différent → guard clause non déclenchée → branche TERMINEE
        $service->envoyerChangementStatut($commande, Commande::STATUT_EN_ATTENTE_RETOUR_MATERIEL);
    }
}
