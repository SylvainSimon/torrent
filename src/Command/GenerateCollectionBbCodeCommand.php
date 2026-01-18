<?php

namespace App\Command;

use App\Service\BbCodeGeneratorService;
use App\Service\TmdbService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:collection:generate-bbcode',
    description: 'Génère une description BBCode d\'une collection de films à partir de TMDB',
)]
class GenerateCollectionBbCodeCommand extends Command
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly BbCodeGeneratorService $bbCodeGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tmdb-id', InputArgument::REQUIRED, 'L\'identifiant TMDB de la collection');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tmdbId = (int) $input->getArgument('tmdb-id');

        try {
            $collectionData = $this->tmdbService->getCollectionDetails($tmdbId);
            $bbcode = $this->bbCodeGenerator->generateCollectionDescription($collectionData);

            $output->writeln($bbcode);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
