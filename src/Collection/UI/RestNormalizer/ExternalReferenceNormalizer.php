<?php

namespace App\Collection\UI\RestNormalizer;

use App\Collection\Domain\ExternalReference;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExternalReferenceNormalizer implements NormalizerInterface
{
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {

        return [
            'uuid'       => $data->getUuid()->toString(),
            'albumUuid'  => $data->getAlbum()->getUuid()->toString(),
            'platform'   => $data->getPlatform(),
            'externalId' => $data->getExternalId(),
            'metadata'   => $data->getMetadata(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ExternalReference;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ExternalReference::class => true,
        ];
    }
}
