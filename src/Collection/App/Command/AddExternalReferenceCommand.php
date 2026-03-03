<?php

namespace App\Collection\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class AddExternalReferenceCommand
{
    public function __construct(
        public Uuid $uuid,
        public ?string $albumUuid,
        public ?string $platform,
        public ?string $externalId,
        public ?array $metadata,
    ) {
    }

    public static function withData(
        Uuid $uuid,
        array $payload,
    ): self {

        $albumUuid = $payload['albumUuid'] ?? null;

        return new self(
            uuid: $uuid,
            albumUuid: is_string($albumUuid) ? $albumUuid : null,
            platform: $payload['platform'] ?? null,
            externalId: $payload['externalId'] ?? null,
            metadata: $payload['metadata'] ?? null,
        );
    }
}
