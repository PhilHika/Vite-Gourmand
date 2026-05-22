<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-users',
    description: 'Liste tous les utilisateurs (id, email, username, role), triés par rôle (ADMIN, SALARIE, USER)',
)]
class ListUserCommand extends Command
{
    /**
     * Ordre de tri des rôles : plus la valeur est petite, plus le rôle remonte.
     */
    private const ORDRE_ROLES = [
        'ROLE_ADMIN'   => 1,
        'ROLE_SALARIE' => 2,
        'ROLE_USER'    => 3,
    ];

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consoleOutput = new SymfonyStyle($input, $output);

        $consoleOutput->title('Liste des utilisateurs');

        // 1. Récupérer tous les utilisateurs
        $utilisateurList = $this->utilisateurRepository->findAll();

        if (empty($utilisateurList)) {
            $consoleOutput->warning('Aucun utilisateur trouvé en BDD.');
            return Command::SUCCESS;
        }

        // 2. Trier par rôle (ADMIN > SALARIE > USER) puis par email
        usort($utilisateurList, function (Utilisateur $premierUtilisateur, Utilisateur $secondUtilisateur) {
            $libellePremier = $premierUtilisateur->getRole()?->getLibelle() ?? 'ROLE_USER';
            $libelleSecond  = $secondUtilisateur->getRole()?->getLibelle() ?? 'ROLE_USER';

            $prioritePremier = self::ORDRE_ROLES[$libellePremier] ?? 99;
            $prioriteSecond  = self::ORDRE_ROLES[$libelleSecond] ?? 99;

            if ($prioritePremier !== $prioriteSecond) {
                return $prioritePremier <=> $prioriteSecond;
            }

            return strcasecmp(
                $premierUtilisateur->getEmail() ?? '',
                $secondUtilisateur->getEmail() ?? ''
            );
        });

        // 3. Construire les lignes du tableau
        $lignesTableau = [];

        foreach ($utilisateurList as $utilisateur) {
            $username = trim(($utilisateur->getPrenom() ?? '') . ' ' . ($utilisateur->getNom() ?? ''));
            $libelleRole = $utilisateur->getRole()?->getLibelle() ?? 'ROLE_USER';

            $lignesTableau[] = [
                $utilisateur->getId(),
                $utilisateur->getEmail(),
                $username,
                $libelleRole,
            ];
        }

        // 4. Affichage
        $consoleOutput->table(
            ['ID', 'Email', 'Username', 'Role'],
            $lignesTableau
        );

        $consoleOutput->success(sprintf('%d utilisateur(s) listé(s).', count($utilisateurList)));

        return Command::SUCCESS;
    }
}
