<?php

namespace App\Tests\Unit\Collection\UI\Exporter;

use App\Collection\UI\Exporter\CsvCollectionExporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsvCollectionExporterTest extends TestCase
{
    private CsvCollectionExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new CsvCollectionExporter();
    }

    #[Test]
    public function streamCollectionAsCsvReturnsValidCsvWithHeadersAndRows(): void
    {
        $collection = $this->createAlbumGenerator([
            [
                'albumUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'title' => 'OK Computer',
                'artist' => 'Radiohead',
                'releaseYear' => 1997,
                'format' => 'Vinyl',
                'genre' => 'Alternative Rock',
                'label' => 'Parlophone',
                'coverUrl' => 'https://example.com/ok-computer.jpg',
                'createdAt' => '2025-01-15 10:30:00',
                'updatedAt' => '2025-01-15 10:30:00',
                'isFavorite' => true,
                'externalReferences' => [
                    [
                        'platform' => 'discogs',
                        'externalId' => '12345',
                        'metadata' => '{"catno":"NODATA 02"}',
                    ],
                ],
            ],
            [
                'albumUuid' => '660e8400-e29b-41d4-a716-446655440001',
                'title' => 'Mezzanine',
                'artist' => 'Massive Attack',
                'releaseYear' => 1998,
                'format' => 'CD',
                'genre' => 'Trip Hop',
                'label' => 'Virgin Records',
                'coverUrl' => null,
                'createdAt' => '2025-02-20 14:00:00',
                'updatedAt' => '2025-02-20 14:00:00',
                'isFavorite' => false,
                'externalReferences' => [],
            ],
        ]);

        $response = $this->exporter->streamCollectionAsCsv($collection);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv', $response->headers->get('Content-Type'));

        $csv = $this->captureStreamedContent($response);
        $lines = array_filter(explode("\n", $csv), fn ($line) => '' !== $line);

        $this->assertCount(3, $lines);

        $headers = str_getcsv($lines[0], ',', '"', '');
        $this->assertSame([
            'album_uuid', 'title', 'artist', 'release_year', 'format',
            'genre', 'label', 'cover_url', 'created_at', 'updated_at',
            'is_favorite', 'external_references',
        ], $headers);

        $firstRow = str_getcsv($lines[1], ',', '"', '');
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $firstRow[0]);
        $this->assertSame('OK Computer', $firstRow[1]);
        $this->assertSame('Radiohead', $firstRow[2]);
        $this->assertSame('1997', $firstRow[3]);

        $decodedRefs = json_decode($firstRow[11], true);
        $this->assertCount(1, $decodedRefs);
        $this->assertSame('discogs', $decodedRefs[0]['platform']);
        $this->assertIsArray($decodedRefs[0]['metadata']);
        $this->assertSame('NODATA 02', $decodedRefs[0]['metadata']['catno']);

        $secondRow = str_getcsv($lines[2], ',', '"', '');
        $this->assertSame('Mezzanine', $secondRow[1]);
        $this->assertSame('Massive Attack', $secondRow[2]);
        $this->assertSame('[]', $secondRow[11]);
    }

    #[Test]
    public function streamCollectionAsCsvWithEmptyCollectionReturnsOnlyHeaders(): void
    {
        $collection = $this->createAlbumGenerator([]);

        $response = $this->exporter->streamCollectionAsCsv($collection);

        $csv = $this->captureStreamedContent($response);
        $lines = array_filter(explode("\n", $csv), fn ($line) => '' !== $line);

        $this->assertCount(1, $lines);

        $headers = str_getcsv($lines[0], ',', '"', '');
        $this->assertSame('album_uuid', $headers[0]);
        $this->assertSame('external_references', $headers[11]);
    }

    private function createAlbumGenerator(array $albums): \Generator
    {
        yield from $albums;
    }

    private function captureStreamedContent($response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }
}
