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
    name: 'app:series:generate-bbcode',
    description: 'Génère une description BBCode complète d\'une série TV à partir de TMDB',
)]
class GenerateSeriesBbCodeCommand extends Command
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly BbCodeGeneratorService $bbCodeGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tmdb-id', InputArgument::REQUIRED, 'L\'identifiant TMDB de la série');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tmdbId = (int) $input->getArgument('tmdb-id');

        try {
            $seriesData = $this->tmdbService->getSeriesDetails($tmdbId);
            $bbcode = $this->bbCodeGenerator->generateSeriesDescription($seriesData);

            $output->writeln($bbcode);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
