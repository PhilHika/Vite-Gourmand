<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\Utilisateur;
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
    name: 'app:create-admin',
    description: 'Initialise les rôles si nécessaire puis crée un utilisateur admin',
)]
class CreateAdminCommand extends Command
{
    /**
     * Les rôles standards à initialiser si absents.
     */
    private const ROLES = [
        'ROLE_USER',
        'ROLE_SALARIE',
        'ROLE_ADMIN',
    ];

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
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email de l\'admin')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe de l\'admin')
            ->addOption('prenom', null, InputOption::VALUE_OPTIONAL, 'Prénom', 'Admin')
            ->addOption('nom', null, InputOption::VALUE_OPTIONAL, 'Nom', 'Admin')
            ->addOption('telephone', null, InputOption::VALUE_OPTIONAL, 'Téléphone', '0000000000')
            ->addOption('ville', null, InputOption::VALUE_OPTIONAL, 'Ville', 'Paris')
            ->addOption('pays', null, InputOption::VALUE_OPTIONAL, 'Pays', 'France')
            ->addOption('adresse', null, InputOption::VALUE_OPTIONAL, 'Adresse postale', 'Adresse admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consoleOutput = new SymfonyStyle($input, $output);

        $consoleOutput->title('Création d\'un admin');

        // 1. Récupérer les options obligatoires
        $email = $input->getOption('email');
        $password = $input->getOption('password');

        if (!$email || !$password) {
            $consoleOutput->error('Les options --email et --password sont obligatoires.');
            return Command::FAILURE;
        }

        // 2. Initialiser les rôles si nécessaire
        $this->initialiserRoles($consoleOutput);

        // 3. Récupérer le rôle ROLE_ADMIN
        $roleAdmin = $this->roleRepository->findOneBy(['libelle' => 'ROLE_ADMIN']);

        if (!$roleAdmin) {
            $consoleOutput->error('Impossible de trouver ou créer le rôle ROLE_ADMIN.');
            return Command::FAILURE;
        }

        // 4. Vérifier si l'utilisateur existe déjà
        $utilisateurExistant = $this->utilisateurRepository->findOneBy(['email' => $email]);

        if ($utilisateurExistant) {
            $consoleOutput->warning(sprintf('Un utilisateur avec l\'email "%s" existe déjà.', $email));
            return Command::SUCCESS;
        }

        // 5. Créer l'utilisateur admin
        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setRole($roleAdmin);
        $utilisateur->setPrenom($input->getOption('prenom'));
        $utilisateur->setNom($input->getOption('nom'));
        $utilisateur->setTelephone($input->getOption('telephone'));
        $utilisateur->setVille($input->getOption('ville'));
        $utilisateur->setPays($input->getOption('pays'));
        $utilisateur->setAdressePostale($input->getOption('adresse'));

        // 6. Hasher le mot de passe
        $motDePasseHashe = $this->passwordHasher->hashPassword($utilisateur, $password);
        $utilisateur->setPassword($motDePasseHashe);

        // 7. Persister
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        $consoleOutput->success(sprintf('Admin créé avec succès : %s', $email));

        return Command::SUCCESS;
    }

    /**
     * Vérifie et crée les rôles manquants dans la table role.
     */
    private function initialiserRoles(SymfonyStyle $consoleOutput): void
    {
        $nombreRolesCrees = 0;

        foreach (self::ROLES as $libelle) {
            $roleExistant = $this->roleRepository->findOneBy(['libelle' => $libelle]);

            if ($roleExistant) {
                continue;
            }

            $role = new Role();
            $role->setLibelle($libelle);
            $this->entityManager->persist($role);
            $nombreRolesCrees++;

            $consoleOutput->text(sprintf('  + Rôle %s créé', $libelle));
        }

        if ($nombreRolesCrees > 0) {
            $this->entityManager->flush();
            $consoleOutput->text('');
        }
    }
}
