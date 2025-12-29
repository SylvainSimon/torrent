<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class QBittorrentService
{
    private ?string $cookie = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $qbittorrentLogger,
        private readonly string              $qbittorrentUrl,
        private readonly string              $qbittorrentUsername,
        private readonly string              $qbittorrentPassword
    )
    {
    }

    public function login(): bool
    {
        try {

            $response = $this->httpClient->request('POST', $this->qbittorrentUrl . '/api/v2/auth/login', [
                'body' => [
                    'username' => $this->qbittorrentUsername,
                    'password' => $this->qbittorrentPassword,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->qbittorrentLogger->error('Échec de la connexion à qBittorrent', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getContent(false)
                ]);
                return false;
            }

            $content = $response->getContent();
            if ($content === 'Fails.') {
                $this->qbittorrentLogger->error('Identifiants invalides pour qBittorrent');
                return false;
            }

            $cookies = $response->getHeaders()['set-cookie'] ?? [];
            foreach ($cookies as $cookie) {
                if (str_starts_with($cookie, 'SID=')) {
                    $cookieParts = explode(';', $cookie);
                    $this->cookie = $cookieParts[0];
                    break;
                }
            }

            if (!$this->cookie) {
                $this->qbittorrentLogger->error('Aucun cookie SID reçu de qBittorrent');
                return false;
            }

            $this->qbittorrentLogger->info('Connexion réussie à qBittorrent');
            return true;

        } catch (Throwable $e) {

            $this->qbittorrentLogger->error('Exception lors de la connexion à qBittorrent', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    public function listTorrents(array $filters = []): array
    {
        try {

            if (!$this->cookie) {
                if (!$this->login()) {
                    $this->qbittorrentLogger->warning('Impossible de lister les torrents : échec de connexion');
                    return [];
                }
            }

            $queryParams = [];

            if (isset($filters['filter'])) {
                $queryParams['filter'] = $filters['filter'];
            }

            if (isset($filters['category'])) {
                $queryParams['category'] = $filters['category'];
            }

            if (isset($filters['sort'])) {
                $queryParams['sort'] = $filters['sort'];
            }

            if (isset($filters['hashes'])) {
                $queryParams['hashes'] = $filters['hashes'];
            }

            $url = $this->qbittorrentUrl . '/api/v2/torrents/info';
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Cookie' => $this->cookie,
                    'Referer' => $this->qbittorrentUrl,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {

                $this->qbittorrentLogger->error('Échec de la récupération de la liste des torrents', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getContent(false)
                ]);

                return [];
            }

            $torrents = $response->toArray();
            $this->qbittorrentLogger->info('Liste des torrents récupérée avec succès', [
                'count' => count($torrents)
            ]);

            return $torrents;

        } catch (Throwable $e) {

            $this->qbittorrentLogger->error('Exception lors de la récupération des torrents', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }
}
