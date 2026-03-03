<?php

namespace App\Collection\App\DTO;

class DiscogsExternalReferenceImport
{
    public function __construct(
        public ?string $externalId,
        public ?array $metadata,
    ) {
    }

    public static function withData(
        array $collection,
    ): self {
        return new self(
            externalId: $collection['release_id'] ?? null,
            metadata: [
                'catalog#'                    => $collection['catalog#'] ?? null,
                'rating'                      => $collection['rating'] ?? null,
                'collectionFolder'            => $collection['collectionFolder'] ?? null,
                'date_added'                  => $collection['date_added'] ?? null,
                'collection_media_condition'  => $collection['collection_media_condition'] ?? null,
                'collection_sleeve_condition' => $collection['collection_sleeve_condition'] ?? null,
                'collection_notes'            => $collection['collection_notes'] ?? null,
            ],
        );
    }
}
