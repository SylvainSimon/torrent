<?php

namespace App\Command;

use App\Service\QBittorrentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:torrent:list',
    description: 'List all torrents from qBittorrent instance',
)]
class ListTorrentsCommand extends Command
{
    public function __construct(
        private readonly QBittorrentService $qBittorrentService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $torrents = $this->qBittorrentService->listTorrents();

        if (empty($torrents)) {
            $io->warning('No torrents found or unable to connect to qBittorrent. Check logs for details.');
            return Command::SUCCESS;
        }

        $io->title('Torrents List');

        $rows = [];
        foreach ($torrents as $torrent) {
            $rows[] = [
                $torrent['name'] ?? 'N/A',
                $torrent['state'] ?? 'N/A',
                $this->formatBytes($torrent['size'] ?? 0),
                round(($torrent['progress'] ?? 0) * 100, 2) . '%',
                $this->formatBytes($torrent['dlspeed'] ?? 0) . '/s',
                $this->formatBytes($torrent['upspeed'] ?? 0) . '/s',
            ];
        }

        $io->table(
            ['Name', 'State', 'Size', 'Progress', 'DL Speed', 'UP Speed'],
            $rows
        );

        $io->success(sprintf('Found %d torrent(s).', count($torrents)));

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
