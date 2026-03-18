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
        array $csvRow,
    ): self {
        return new self(
            externalId: $csvRow['release_id'] ?? null,
            metadata: [
                'catalog#'                    => $csvRow['catalog#'] ?? null,
                'rating'                      => $csvRow['rating'] ?? null,
                'collectionFolder'            => $csvRow['collectionfolder'] ?? null,
                'date_added'                  => $csvRow['date_added'] ?? null,
                'collection_media_condition'  => $csvRow['collection_media_condition'] ?? null,
                'collection_sleeve_condition' => $csvRow['collection_sleeve_condition'] ?? null,
                'collection_notes'            => $csvRow['collection_notes'] ?? null,
            ],
        );
    }
}
