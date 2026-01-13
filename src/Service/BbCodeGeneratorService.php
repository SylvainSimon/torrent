<?php

namespace App\Service;

class BbCodeGeneratorService
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function generateMovieDescription(array $movieData): string
    {
        $templatePath = $this->projectDir . '/public/presentation.txt';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        $template = file_get_contents($templatePath);

        $actorsPhotos = array_map(
            fn($url) => '[img]' . $url . '[/img]',
            $movieData['actors_photos'] ?? []
        );

        $replacements = [
            '{{TITLE}}' => $movieData['title'] ?? 'N/A',
            '{{POSTER_URL}}' => $movieData['poster_url'] ?? '',
            '{{COUNTRIES}}' => implode(', ', $movieData['countries'] ?? []),
            '{{RELEASE_DATE}}' => $movieData['release_date'] ?? 'N/A',
            '{{ORIGINAL_TITLE}}' => $movieData['original_title'] ?? 'N/A',
            '{{RUNTIME}}' => $movieData['runtime_formatted'] ?? 'N/A',
            '{{DIRECTORS}}' => implode(', ', $movieData['directors'] ?? []),
            '{{CAST}}' => implode(', ', $movieData['actors'] ?? []),
            '{{GENRES}}' => implode(', ', $movieData['genres'] ?? []),
            '{{RATING}}' => $movieData['rating'] ?? 'N/A',
            '{{RATING_BADGE}}' => $movieData['rating_badge'] ?? '',
            '{{OVERVIEW}}' => $movieData['overview'] ?? '',
            '{{ACTORS_PHOTOS}}' => implode(' ', $actorsPhotos),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function generateSeriesDescription(array $seriesData): string
    {
        $templatePath = $this->projectDir . '/public/presentation_series.txt';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        $template = file_get_contents($templatePath);

        $actorsPhotos = array_map(
            fn($url) => '[img]' . $url . '[/img]',
            $seriesData['actors_photos'] ?? []
        );

        $replacements = [
            '{{TITLE}}' => $seriesData['title'] ?? 'N/A',
            '{{POSTER_URL}}' => $seriesData['poster_url'] ?? '',
            '{{COUNTRIES}}' => implode(', ', $seriesData['countries'] ?? []),
            '{{FIRST_AIR_DATE}}' => $seriesData['first_air_date'] ?? 'N/A',
            '{{LAST_AIR_DATE}}' => $seriesData['last_air_date'] ?? 'N/A',
            '{{ORIGINAL_TITLE}}' => $seriesData['original_title'] ?? 'N/A',
            '{{SEASONS_COUNT}}' => $seriesData['seasons_count'] ?? 'N/A',
            '{{EPISODES_COUNT}}' => $seriesData['episodes_count'] ?? 'N/A',
            '{{CREATORS}}' => implode(', ', $seriesData['creators'] ?? []),
            '{{CAST}}' => implode(', ', $seriesData['actors'] ?? []),
            '{{GENRES}}' => implode(', ', $seriesData['genres'] ?? []),
            '{{RATING}}' => $seriesData['rating'] ?? 'N/A',
            '{{RATING_BADGE}}' => $seriesData['rating_badge'] ?? '',
            '{{OVERVIEW}}' => $seriesData['overview'] ?? '',
            '{{ACTORS_PHOTOS}}' => implode(' ', $actorsPhotos),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function generateSeasonDescription(array $seasonData): string
    {
        $templatePath = $this->projectDir . '/public/presentation_season.txt';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Template file not found: ' . $templatePath);
        }

        $template = file_get_contents($templatePath);

        $actorsPhotos = array_map(
            fn($url) => '[img]' . $url . '[/img]',
            $seasonData['actors_photos'] ?? []
        );

        $replacements = [
            '{{TITLE}}' => $seasonData['title'] ?? 'N/A',
            '{{SEASON_NUMBER}}' => $seasonData['season_number'] ?? 'N/A',
            '{{POSTER_URL}}' => $seasonData['poster_url'] ?? '',
            '{{COUNTRIES}}' => implode(', ', $seasonData['countries'] ?? []),
            '{{AIR_DATE}}' => $seasonData['air_date'] ?? 'N/A',
            '{{ORIGINAL_TITLE}}' => $seasonData['original_title'] ?? 'N/A',
            '{{EPISODES_COUNT}}' => $seasonData['episodes_count'] ?? 'N/A',
            '{{CREATORS}}' => implode(', ', $seasonData['creators'] ?? []),
            '{{CAST}}' => implode(', ', $seasonData['actors'] ?? []),
            '{{GENRES}}' => implode(', ', $seasonData['genres'] ?? []),
            '{{RATING}}' => $seasonData['rating'] ?? 'N/A',
            '{{RATING_BADGE}}' => $seasonData['rating_badge'] ?? '',
            '{{OVERVIEW}}' => $seasonData['overview'] ?? '',
            '{{ACTORS_PHOTOS}}' => implode(' ', $actorsPhotos),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
