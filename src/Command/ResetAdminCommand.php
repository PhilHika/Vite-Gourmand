<?php

namespace App\Command;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-admin',
    description: 'Réinitialiser le mot de passe des comptes admin',
)]
class ResetAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private RoleRepository $roleRepository,
        private UtilisateurRepository $utilisateurRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email d\'un admin spécifique à réinitialiser')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe à appliquer (même mot de passe pour tous les admins)')
            ->setHelp(<<<'HELP'
                Trois modes d'utilisation :

                  <info>Mode interactif</info> (recommandé) :
                    php bin/console app:reset-admin
                    → Liste tous les admins et demande un mot de passe pour chacun.

                  <info>Mode batch</info> (non-interactif) :
                    php bin/console app:reset-admin --password=NouveauMotDePasse
                    → Applique le même mot de passe à tous les admins.

                  <info>Mode ciblé</info> (un seul admin) :
                    php bin/console app:reset-admin --email=admin@example.fr --password=NouveauMotDePasse
                    → Réinitialise le mot de passe d'un admin spécifique.
                HELP)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consoleOutput = new SymfonyStyle($input, $output);

        $consoleOutput->title('Reset des mots de passe admin');

        // 1. Vérifier que le rôle ROLE_ADMIN existe
        $roleAdmin = $this->roleRepository->findOneBy(['libelle' => 'ROLE_ADMIN']);

        if (!$roleAdmin) {
            $consoleOutput->error('Le rôle ROLE_ADMIN n\'existe pas en BDD. Exécutez d\'abord : php bin/console app:create-admin');
            return Command::FAILURE;
        }

        // 2. Récupérer les options
        $emailCible = $input->getOption('email');
        $motDePasseGlobal = $input->getOption('password');

        // 3. Mode ciblé : un seul admin
        if ($emailCible) {
            return $this->resetAdminCible($consoleOutput, $emailCible, $motDePasseGlobal, $roleAdmin);
        }

        // 4. Récupérer tous les admins
        $administrateurs = $this->utilisateurRepository->findBy(['role' => $roleAdmin]);

        if (empty($administrateurs)) {
            $consoleOutput->warning('Aucun compte admin trouvé en BDD.');
            return Command::SUCCESS;
        }

        $consoleOutput->text(sprintf('%d compte(s) admin trouvé(s) :', count($administrateurs)));
        $consoleOutput->text('');

        // 5. Mode batch (--password fourni) ou mode interactif
        $nombreMisAJour = 0;

        foreach ($administrateurs as $index => $administrateur) {
            $position = sprintf('[%d/%d]', $index + 1, count($administrateurs));
            $consoleOutput->text(sprintf(' %s <info>%s</info>', $position, $administrateur->getEmail()));

            if ($motDePasseGlobal) {
                // Mode batch : même mot de passe pour tous
                $nouveauMotDePasse = $motDePasseGlobal;
            } else {
                // Mode interactif : demander le mot de passe (caché)
                $nouveauMotDePasse = $consoleOutput->askHidden('Nouveau mot de passe');

                if (!$nouveauMotDePasse) {
                    $consoleOutput->text(' ⤳ Ignoré (aucun mot de passe saisi)');
                    $consoleOutput->text('');
                    continue;
                }
            }

            $motDePasseHashe = $this->passwordHasher->hashPassword($administrateur, $nouveauMotDePasse);
            $administrateur->setPassword($motDePasseHashe);
            $nombreMisAJour++;

            $consoleOutput->text(' ✓ Mot de passe mis à jour');
            $consoleOutput->text('');
        }

        $this->entityManager->flush();

        $consoleOutput->success(sprintf('%d mot(s) de passe mis à jour avec succès.', $nombreMisAJour));

        return Command::SUCCESS;
    }

    /**
     * Réinitialise le mot de passe d'un admin spécifique (mode ciblé).
     */
    private function resetAdminCible(
        SymfonyStyle $consoleOutput,
        string $emailCible,
        ?string $motDePasse,
        Role $roleAdmin
    ): int {
        $administrateur = $this->utilisateurRepository->findOneBy([
            'email' => $emailCible,
            'role' => $roleAdmin,
        ]);

        if (!$administrateur) {
            $consoleOutput->error(sprintf('Aucun admin trouvé avec l\'email "%s".', $emailCible));
            return Command::FAILURE;
        }

        // Demander le mot de passe s'il n'est pas fourni
        if (!$motDePasse) {
            $motDePasse = $consoleOutput->askHidden(sprintf('Nouveau mot de passe pour %s', $emailCible));

            if (!$motDePasse) {
                $consoleOutput->warning('Aucun mot de passe saisi. Opération annulée.');
                return Command::SUCCESS;
            }
        }

        $motDePasseHashe = $this->passwordHasher->hashPassword($administrateur, $motDePasse);
        $administrateur->setPassword($motDePasseHashe);
        $this->entityManager->flush();

        $consoleOutput->success(sprintf('Mot de passe mis à jour pour %s', $emailCible));

        return Command::SUCCESS;
    }
}
