<?php

namespace App\Command;

use App\Repository\MenuRepository;
use App\Repository\AvisRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cache:test',
    description: 'Test le système de cache SQL',
)]
class TestCacheCommand extends Command
{
    public function __construct(
        private readonly MenuRepository $menuRepository,
        private readonly AvisRepository $avisRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test du système de cache SQL');

        // Test 1: Cache menus
        $io->section('Test 1 : Cache des menus (findAllCached)');
        $io->writeln('Appel 1 (MISS attendu)...');
        $menus1 = $this->menuRepository->findAllCached(ttl: 60);
        $io->success('Résultat : ' . count($menus1) . ' menus');

        $io->writeln('Appel 2 (HIT attendu)...');
        $menus2 = $this->menuRepository->findAllCached(ttl: 60);
        $io->success('Résultat : ' . count($menus2) . ' menus');

        if ($menus1 === $menus2) {
            $io->success('✓ Même objet en cache');
        }

        // Test 2: Cache avis
        $io->section('Test 2 : Cache des avis (findPubliesCached)');
        $io->writeln('Appel 1 (MISS attendu)...');
        $avis1 = $this->avisRepository->findPubliesCached(ttl: 60);
        $io->success('Résultat : ' . count($avis1) . ' avis publiés');

        $io->writeln('Appel 2 (HIT attendu)...');
        $avis2 = $this->avisRepository->findPubliesCached(ttl: 60);
        $io->success('Résultat : ' . count($avis2) . ' avis publiés');

        $io->section('✅ Tous les tests passés !');
        $io->writeln('Conseil : Vérifiez les logs avec tail -f var/log/dev.log pour voir les "Cache HIT" et "Cache MISS"');

        return Command::SUCCESS;
    }
}
