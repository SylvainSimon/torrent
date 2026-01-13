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
            '{{OVERVIEW}}' => $movieData['overview'] ?? '',
            '{{ACTORS_PHOTOS}}' => implode("\n", $actorsPhotos),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
