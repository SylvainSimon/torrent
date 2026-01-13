<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbService
{
    private const API_BASE_URL = 'https://api.themoviedb.org/3';
    private const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/original';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $tmdbApiKey
    ) {
    }

    public function getMovieDetails(int $movieId): array
    {
        $response = $this->httpClient->request('GET', self::API_BASE_URL . '/movie/' . $movieId, [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
                'append_to_response' => 'credits',
            ],
        ]);

        $data = $response->toArray();

        $releaseDate = $data['release_date'] ?? '';
        $releaseDateFormatted = $this->formatDate($releaseDate);

        $countries = array_column($data['production_countries'] ?? [], 'name');
        $countriesTranslated = array_map(fn($country) => $this->translateCountry($country), $countries);

        return [
            'title' => $data['title'] ?? '',
            'original_title' => $data['original_title'] ?? '',
            'tagline' => $data['tagline'] ?? '',
            'overview' => $data['overview'] ?? '',
            'poster_url' => isset($data['poster_path']) ? self::IMAGE_BASE_URL . $data['poster_path'] : '',
            'release_date' => $releaseDateFormatted,
            'runtime' => $data['runtime'] ?? 0,
            'genres' => array_column($data['genres'] ?? [], 'name'),
            'countries' => $countriesTranslated,
            'rating' => isset($data['vote_average']) ? round($data['vote_average'], 1) . '/10' : 'N/A',
            'directors' => $this->extractDirectors($data['credits'] ?? []),
            'actors' => $this->extractActors($data['credits'] ?? []),
            'actors_photos' => $this->extractActorsPhotos($data['credits'] ?? []),
        ];
    }

    private function extractDirectors(array $credits): array
    {
        $directors = [];
        foreach ($credits['crew'] ?? [] as $crew) {
            if ($crew['job'] === 'Director') {
                $directors[] = $crew['name'];
            }
        }
        return $directors;
    }

    private function extractActors(array $credits, int $limit = 6): array
    {
        $actors = [];
        $cast = array_slice($credits['cast'] ?? [], 0, $limit);
        foreach ($cast as $actor) {
            $actors[] = $actor['name'];
        }
        return $actors;
    }

    private function extractActorsPhotos(array $credits, int $limit = 4): array
    {
        $photos = [];
        $cast = array_slice($credits['cast'] ?? [], 0, $limit);
        foreach ($cast as $actor) {
            if (!empty($actor['profile_path'])) {
                $photos[] = 'https://image.tmdb.org/t/p/w138_and_h175_face' . $actor['profile_path'];
            }
        }
        return $photos;
    }

    private function formatDate(string $date): string
    {
        if (empty($date)) {
            return 'N/A';
        }

        try {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateTime) {
                return $date;
            }

            $months = [
                1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
            ];

            $day = $dateTime->format('j');
            $month = $months[(int)$dateTime->format('n')];
            $year = $dateTime->format('Y');

            return sprintf('%d %s %s', $day, $month, $year);
        } catch (\Exception $e) {
            return $date;
        }
    }

    private function translateCountry(string $country): string
    {
        $translations = [
            'United States of America' => 'États-Unis',
            'United Kingdom' => 'Royaume-Uni',
            'France' => 'France',
            'Germany' => 'Allemagne',
            'Spain' => 'Espagne',
            'Italy' => 'Italie',
            'Japan' => 'Japon',
            'China' => 'Chine',
            'South Korea' => 'Corée du Sud',
            'Canada' => 'Canada',
            'Australia' => 'Australie',
            'Brazil' => 'Brésil',
            'Mexico' => 'Mexique',
            'India' => 'Inde',
            'Russia' => 'Russie',
            'Belgium' => 'Belgique',
            'Netherlands' => 'Pays-Bas',
            'Switzerland' => 'Suisse',
            'Austria' => 'Autriche',
            'Sweden' => 'Suède',
            'Norway' => 'Norvège',
            'Denmark' => 'Danemark',
            'Poland' => 'Pologne',
            'Ireland' => 'Irlande',
            'New Zealand' => 'Nouvelle-Zélande',
        ];

        return $translations[$country] ?? $country;
    }

    public function formatRuntime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%dh%02d', $hours, $mins);
    }
}
