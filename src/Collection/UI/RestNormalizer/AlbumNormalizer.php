<?php

namespace App\Collection\UI\RestNormalizer;

use App\Collection\Domain\Album;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class AlbumNormalizer implements NormalizerInterface
{
    public function __construct(
        private ExternalReferenceNormalizer $externalReferenceNormalizer,
    ) {
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|\ArrayObject|null {

        return [
            'uuid'               => $data->getUuid()->toString(),
            'ownerUuid'          => $data->getOwnerUuid()->toString(),
            'title'              => $data->getTitle(),
            'artist'             => $data->getArtist(),
            'genre'              => $data->getGenre(),
            'releaseYear'        => $data->getReleaseYear(),
            'format'             => $data->getFormat(),
            'isFavorite'         => $data->isFavorite(),
            'label'              => $data->getLabel(),
            'coverUrl'           => $data->getCoverUrl(),
            'createdAt'          => $data->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt'          => $data->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'externalReferences' => array_map(
                fn ($externalReference) => $this->externalReferenceNormalizer->normalize($externalReference),
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
