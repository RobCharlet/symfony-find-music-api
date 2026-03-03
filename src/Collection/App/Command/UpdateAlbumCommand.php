<?php

namespace App\Collection\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateAlbumCommand
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $ownerUuid,
        public bool $isAdmin,
        public ?string $title,
        public ?string $artist,
        public mixed $releaseYear,
        public ?string $format,
        public ?string $genre,
        public ?string $label,
        public ?string $coverUrl,
    ) {
    }

    public static function withData(
        Uuid $uuid,
        Uuid $ownerUuid,
        bool $isAdmin,
        array $payload,
    ): self {
        return new self(
            uuid: $uuid,
            ownerUuid: $ownerUuid,
            isAdmin: $isAdmin,
            title: $payload['title'] ?? null,
            artist: $payload['artist'] ?? null,
            releaseYear: $payload['releaseYear'] ?? null,
            format: $payload['format'] ?? null,
            genre: $payload['genre'] ?? null,
            label: $payload['label'] ?? null,
            coverUrl: $payload['coverUrl'] ?? null,
        );
    }
}
