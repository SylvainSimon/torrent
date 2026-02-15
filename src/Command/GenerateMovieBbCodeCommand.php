<?php

namespace App\Command;

use App\Service\BbCodeGeneratorService;
use App\Service\TmdbService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

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

            $this->copyToClipboard($bbcode, $output);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Erreur: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function copyToClipboard(string $content, OutputInterface $output): void
    {
        $os = PHP_OS_FAMILY;

        try {
            if ($os === 'Windows') {
                $process = new Process(['clip']);
                $process->setInput($content);
                $process->run();
            } elseif ($os === 'Darwin') {
                $process = new Process(['pbcopy']);
                $process->setInput($content);
                $process->run();
            } elseif ($os === 'Linux') {
                $process = new Process(['xclip', '-selection', 'clipboard']);
                $process->setInput($content);
                $process->run();
            } else {
                $output->writeln('<comment>Copie automatique non supportée sur ce système d\'exploitation.</comment>');
                return;
            }

            if ($process->isSuccessful()) {
                $output->writeln('<info>✓ Contenu copié dans le presse-papier</info>');
            } else {
                $output->writeln('<error>Erreur lors de la copie: ' . $process->getErrorOutput() . '</error>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>Impossible de copier dans le presse-papier: ' . $e->getMessage() . '</error>');
        }
    }
}
