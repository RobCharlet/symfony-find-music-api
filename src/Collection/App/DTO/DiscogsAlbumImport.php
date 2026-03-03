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

    public static function withData(array $collection): self
    {
        $released = trim((string) ($collection['released'] ?? ''));
        $releaseYear = ctype_digit($released) ? (int) $released : null;

        if (null !== $releaseYear && ($releaseYear < 1900 || $releaseYear > 2100)) {
            $releaseYear = null;
        }

        return new self(
            title: $collection['title'] ?? null,
            artist: $collection['artist'] ?? null,
            releaseYear: $releaseYear,
            format: $collection['format'] ?? null,
            label: $collection['label'] ?? null,
        );
    }
}
