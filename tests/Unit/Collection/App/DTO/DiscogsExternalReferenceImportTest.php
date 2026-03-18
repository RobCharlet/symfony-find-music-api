<?php

namespace App\Tests\Unit\Collection\App\DTO;

use App\Collection\App\DTO\DiscogsExternalReferenceImport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscogsExternalReferenceImportTest extends TestCase
{
    #[Test]
    public function withDataMapsAllFieldsFromNormalizedCsvRow()
    {
        $csvRow = [
            'release_id'                  => '2596660',
            'catalog#'                    => 'piasr 210 lp',
            'rating'                      => '4',
            'collectionFolder'            => 'Uncategorized',
            'date_added'                  => '2026-01-19 11:01:49',
            'collection_media_condition'  => 'Near Mint (NM or M-)',
            'collection_sleeve_condition' => 'Near Mint (NM or M-)',
            'collection_notes'            => 'First pressing',
        ];

        $dto = DiscogsExternalReferenceImport::withData($csvRow);

        $this->assertSame('2596660', $dto->externalId);
        $this->assertSame('piasr 210 lp', $dto->metadata['catalog#']);
        $this->assertSame('4', $dto->metadata['rating']);
        $this->assertSame('Uncategorized', $dto->metadata['collection_folder']);
        $this->assertSame('2026-01-19 11:01:49', $dto->metadata['date_added']);
        $this->assertSame('Near Mint (NM or M-)', $dto->metadata['collection_media_condition']);
        $this->assertSame('Near Mint (NM or M-)', $dto->metadata['collection_sleeve_condition']);
        $this->assertSame('First pressing', $dto->metadata['collection_notes']);
    }

    #[Test]
    public function withDataReturnsNullForMissingFields()
    {
        $dto = DiscogsExternalReferenceImport::withData([]);

        $this->assertNull($dto->externalId);
        $this->assertNull($dto->metadata['catalog#']);
        $this->assertNull($dto->metadata['rating']);
        $this->assertNull($dto->metadata['collection_folder']);
        $this->assertNull($dto->metadata['date_added']);
        $this->assertNull($dto->metadata['collection_media_condition']);
        $this->assertNull($dto->metadata['collection_sleeve_condition']);
        $this->assertNull($dto->metadata['collection_notes']);
    }
}
