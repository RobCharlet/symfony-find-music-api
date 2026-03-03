<?php

namespace App\Collection\Domain;

use Symfony\Component\Uid\Uuid;

class ExternalReference
{
    public function __construct(
        private readonly Uuid $uuid,
        private Album $album,
        private PlatformEnum $platform,
        private string $externalId,
        private ?array $metadata = null,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getPlatform(): string
    {
        return $this->platform->value;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function update(
        PlatformEnum $platform,
        string $externalId,
        ?array $metadata,
    ): void {
        $this->platform = $platform;
        $this->externalId = $externalId;
        $this->metadata = $metadata;
    }
}
