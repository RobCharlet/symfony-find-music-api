<?php

namespace App\Collection\Domain;

use Symfony\Component\Uid\Uuid;

final class Album
{
    public function __construct(
        private readonly Uuid $uuid,
        private readonly Uuid $ownerUuid,
        private string $title,
        private string $artist,
        private string $format,
        private bool $isFavorite = false,
        private ?int $releaseYear = null,
        private ?string $genre  = null,
        private ?string $label = null,
        private ?string $coverUrl = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable(),
        private iterable $externalReferences = [],
    ) {
    }

    public function getArtist(): string
    {
        return $this->artist;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getOwnerUuid(): Uuid
    {
        return $this->ownerUuid;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getExternalReferences(): iterable
    {
        return $this->externalReferences;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function update(
        string $title,
        string $artist,
        string $format,
        bool $isFavorite,
        ?int $releaseYear,
        ?string $genre,
        ?string $label,
        ?string $coverUrl,
    ): void {
        $this->title = $title;
        $this->artist = $artist;
        $this->releaseYear = $releaseYear;
        $this->format = $format;
        $this->isFavorite = $isFavorite;
        $this->genre = $genre;
        $this->label = $label;
        $this->coverUrl = $coverUrl;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
