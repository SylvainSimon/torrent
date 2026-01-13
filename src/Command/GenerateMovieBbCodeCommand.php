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
    name: 'app:movie:generate-bbcode',
    description: 'Génère une description BBCode complète d\'un film à partir de TMDB',
)]
class GenerateMovieBbCodeCommand extends Command
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly BbCodeGeneratorService $bbCodeGenerator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tmdb-id', InputArgument::REQUIRED, 'L\'identifiant TMDB du film');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tmdbId = (int) $input->getArgument('tmdb-id');

        try {
            $movieData = $this->tmdbService->getMovieDetails($tmdbId);
            $movieData['runtime_formatted'] = $this->tmdbService->formatRuntime($movieData['runtime']);

            $bbcode = $this->bbCodeGenerator->generateMovieDescription($movieData);

            $output->writeln($bbcode);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
