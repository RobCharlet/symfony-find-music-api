<?php

namespace App\Collection\UI\RestNormalizer;

use App\Collection\Domain\Album;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class PublicAlbumNormalizer implements NormalizerInterface
{
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {
        return [
            'albumUuid' => $data->getUuid()->toString(),
            'title' => $data->getTitle(),
            'artist' => $data->getArtist(),
            'releaseYear' => $data->getReleaseYear(),
            'format' => $data->getFormat(),
            'genre' => $data->getGenre(),
            'label' => $data->getLabel(),
            'coverUrl' => $data->getCoverUrl(),
            'createdAt' => $data->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $data->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'isFavorite' => $data->isFavorite(),
            'externalReferences' => array_map(
                fn ($externalReference) => [
                    'referenceUuid' => $externalReference->getUuid()->toString(),
                    'platform' => $externalReference->getPlatform()->value,
                    'metadata' => $externalReference->getMetadata(),
                ],
                iterator_to_array($data->getExternalReferences())
            ),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Album;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Album::class => true,
        ];
    }
}
