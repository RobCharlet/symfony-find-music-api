<?php

namespace App\Collection\UI\ReadModel;

use App\Collection\Domain\Album;
use Symfony\Component\JsonStreamer\Attribute\JsonStreamable;

/**
 * JsonStreamer view model mirroring AlbumNormalizer output.
 */
#[JsonStreamable]
class AlbumView
{
    /**
     * @param list<ExternalReferenceView> $externalReferences
     */
    public function __construct(
        public string $uuid,
        public string $ownerUuid,
        public string $title,
        public string $artist,
        public ?string $genre,
        public ?int $releaseYear,
        public string $format,
        public bool $isFavorite,
        public ?string $label,
        public ?string $coverUrl,
        public string $createdAt,
        public string $updatedAt,
        public array $externalReferences,
        public ?int $rating,
        public ?string $personalNote,
    ) {
    }

    public static function fromAlbum(Album $album): self
    {
        $externalReferences = [];
        foreach ($album->getExternalReferences() as $externalReference) {
            $externalReferences[] = ExternalReferenceView::fromExternalReference($externalReference);
        }

        return new self(
            $album->getUuid()->toString(),
            $album->getOwnerUuid()->toString(),
            $album->getTitle(),
            $album->getArtist(),
            $album->getGenre(),
            $album->getReleaseYear(),
            $album->getFormat(),
            $album->isFavorite(),
            $album->getLabel(),
            $album->getCoverUrl(),
            $album->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $album->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            $externalReferences,
            $album->getRating(),
            $album->getPersonalNote(),
        );
    }
}
