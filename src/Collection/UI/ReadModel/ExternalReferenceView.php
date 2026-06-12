<?php

namespace App\Collection\UI\ReadModel;

use App\Collection\Domain\ExternalReference;
use Symfony\Component\JsonStreamer\Attribute\JsonStreamable;

/**
 * JsonStreamer view model mirroring ExternalReferenceNormalizer output.
 */
#[JsonStreamable]
class ExternalReferenceView
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $uuid,
        public string $albumUuid,
        public string $platform,
        public string $externalId,
        public ?array $metadata,
    ) {
    }

    public static function fromExternalReference(ExternalReference $externalReference): self
    {
        return new self(
            $externalReference->getUuid()->toString(),
            $externalReference->getAlbum()->getUuid()->toString(),
            $externalReference->getPlatform()->value,
            $externalReference->getExternalId(),
            $externalReference->getMetadata(),
        );
    }
}
