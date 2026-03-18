<?php

namespace App\Collection\App\DTO;

class DiscogsAlbumImport
{
    public function __construct(
        public ?string $title,
        public ?string $artist,
        public ?int $releaseYear,
        public ?string $format,
        public ?string $label,
    ) {
    }

    public static function withData(array $csvRow): self
    {
        $released = trim((string) ($csvRow['released'] ?? ''));
        $releaseYear = ctype_digit($released) ? (int) $released : null;

        if (null !== $releaseYear && ($releaseYear < 1900 || $releaseYear > 2100)) {
            $releaseYear = null;
        }

        return new self(
            title: $csvRow['title'] ?? null,
            artist: $csvRow['artist'] ?? null,
            releaseYear: $releaseYear,
            format: $csvRow['format'] ?? null,
            label: $csvRow['label'] ?? null,
        );
    }
}
