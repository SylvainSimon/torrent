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

        $rating = $data['vote_average'] ?? 0;
        $ratingBadge = $rating > 0 ? $this->generateRatingBadge($rating) : '';

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
            'rating_badge' => $ratingBadge,
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

    public function getSeriesDetails(int $seriesId): array
    {
        $response = $this->httpClient->request('GET', self::API_BASE_URL . '/tv/' . $seriesId, [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
                'append_to_response' => 'credits',
            ],
        ]);

        $data = $response->toArray();

        $firstAirDate = $data['first_air_date'] ?? '';
        $lastAirDate = $data['last_air_date'] ?? '';

        $countries = array_column($data['origin_country'] ?? [], null);
        $countriesTranslated = array_map(fn($code) => $this->translateCountryCode($code), $countries);

        $rating = $data['vote_average'] ?? 0;
        $ratingBadge = $rating > 0 ? $this->generateRatingBadge($rating) : '';

        return [
            'title' => $data['name'] ?? '',
            'original_title' => $data['original_name'] ?? '',
            'overview' => $data['overview'] ?? '',
            'poster_url' => isset($data['poster_path']) ? self::IMAGE_BASE_URL . $data['poster_path'] : '',
            'first_air_date' => $this->formatDate($firstAirDate),
            'last_air_date' => $this->formatDate($lastAirDate),
            'seasons_count' => $data['number_of_seasons'] ?? 0,
            'episodes_count' => $data['number_of_episodes'] ?? 0,
            'genres' => array_column($data['genres'] ?? [], 'name'),
            'countries' => $countriesTranslated,
            'rating' => isset($data['vote_average']) ? round($data['vote_average'], 1) . '/10' : 'N/A',
            'rating_badge' => $ratingBadge,
            'creators' => array_column($data['created_by'] ?? [], 'name'),
            'actors' => $this->extractActors($data['credits'] ?? []),
            'actors_photos' => $this->extractActorsPhotos($data['credits'] ?? []),
        ];
    }

    public function getSeasonDetails(int $seriesId, int $seasonNumber): array
    {
        // Récupérer d'abord les infos de la série pour avoir les métadonnées générales
        $seriesResponse = $this->httpClient->request('GET', self::API_BASE_URL . '/tv/' . $seriesId, [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
                'append_to_response' => 'credits',
            ],
        ]);
        $seriesData = $seriesResponse->toArray();

        // Récupérer les détails de la saison
        $seasonResponse = $this->httpClient->request('GET', self::API_BASE_URL . '/tv/' . $seriesId . '/season/' . $seasonNumber, [
            'query' => [
                'api_key' => $this->tmdbApiKey,
                'language' => 'fr-FR',
            ],
        ]);
        $seasonData = $seasonResponse->toArray();

        $airDate = $seasonData['air_date'] ?? '';

        $countries = array_column($seriesData['origin_country'] ?? [], null);
        $countriesTranslated = array_map(fn($code) => $this->translateCountryCode($code), $countries);

        $rating = $seriesData['vote_average'] ?? 0;
        $ratingBadge = $rating > 0 ? $this->generateRatingBadge($rating) : '';

        return [
            'title' => $seriesData['name'] ?? '',
            'original_title' => $seriesData['original_name'] ?? '',
            'overview' => $seasonData['overview'] ?: $seriesData['overview'] ?? '',
            'poster_url' => isset($seasonData['poster_path']) ? self::IMAGE_BASE_URL . $seasonData['poster_path'] : '',
            'season_number' => $seasonNumber,
            'air_date' => $this->formatDate($airDate),
            'episodes_count' => count($seasonData['episodes'] ?? []),
            'genres' => array_column($seriesData['genres'] ?? [], 'name'),
            'countries' => $countriesTranslated,
            'rating' => isset($seriesData['vote_average']) ? round($seriesData['vote_average'], 1) . '/10' : 'N/A',
            'rating_badge' => $ratingBadge,
            'creators' => array_column($seriesData['created_by'] ?? [], 'name'),
            'actors' => $this->extractActors($seriesData['credits'] ?? []),
            'actors_photos' => $this->extractActorsPhotos($seriesData['credits'] ?? []),
        ];
    }

    private function translateCountryCode(string $code): string
    {
        $translations = [
            'US' => 'États-Unis',
            'GB' => 'Royaume-Uni',
            'FR' => 'France',
            'DE' => 'Allemagne',
            'ES' => 'Espagne',
            'IT' => 'Italie',
            'JP' => 'Japon',
            'CN' => 'Chine',
            'KR' => 'Corée du Sud',
            'CA' => 'Canada',
            'AU' => 'Australie',
            'BR' => 'Brésil',
            'MX' => 'Mexique',
            'IN' => 'Inde',
            'RU' => 'Russie',
            'BE' => 'Belgique',
            'NL' => 'Pays-Bas',
            'CH' => 'Suisse',
            'AT' => 'Autriche',
            'SE' => 'Suède',
            'NO' => 'Norvège',
            'DK' => 'Danemark',
            'PL' => 'Pologne',
            'IE' => 'Irlande',
            'NZ' => 'Nouvelle-Zélande',
        ];

        return $translations[$code] ?? $code;
    }

    public function formatRuntime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%dh%02d', $hours, $mins);
    }

    public function generateRatingBadge(float $rating): string
    {
        // Déterminer la couleur en fonction de la note
        $color = 'red';
        if ($rating >= 7) {
            $color = 'brightgreen';
        } elseif ($rating >= 5) {
            $color = 'yellow';
        } elseif ($rating >= 3) {
            $color = 'orange';
        }

        // Encoder la note pour l'URL
        $label = urlencode('Note');
        $value = urlencode(number_format($rating, 1) . '/10');

        return "https://img.shields.io/badge/{$label}-{$value}-{$color}?style=for-the-badge&logo=star&logoColor=white";
    }
}
