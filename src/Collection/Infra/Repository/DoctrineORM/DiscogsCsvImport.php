<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\App\DTO\DiscogsAlbumImport;
use App\Collection\App\DTO\DiscogsExternalReferenceImport;
use App\Collection\Domain\Album;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use App\Collection\Domain\Repository\CsvImportInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

readonly class DiscogsCsvImport implements CsvImportInterface
{
    public function __construct(
        private AlbumWriterInterface $albumWriter,
        private EntityManagerInterface $entityManager,
        private ExternalReferenceReaderInterface $externalReferenceReader,
        private ExternalReferenceWriterInterface $externalReferenceWriter,
    ) {
    }

    public function import(
        string $filePath,
        Uuid $userUuid,
    ): array {
        $decoder = new CsvEncoder();
        $dbConnection = $this->entityManager->getConnection();
        $results = [
            'total'    => 0,
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        // Normalize CSV header
        $csvFile = fopen($filePath, 'r');
        $csvHeader = fgetcsv(
            stream: $csvFile,
            escape: ''
        );
        fclose($csvFile);

        $normalizedCsvHeader = array_map(
            function (string $header) {
                $header = trim(strtolower($header));

                return str_replace(' ', '_', $header);
            },
            $csvHeader
        );

        // Check mandatory columns
        $requiredColumns = ['title', 'artist', 'released', 'format', 'label', 'release_id'];
        $missingColumns = array_diff($requiredColumns, $normalizedCsvHeader);

        if ([] !== $missingColumns) {
            foreach ($missingColumns as $missingColumn) {
                $results['errors'][] = [
                    'message' => sprintf('%s column is missing.', $missingColumn),
                ];
            }

            return $results;
        }

        // Decode CSV
        try {
            $content = $decoder->decode(file_get_contents($filePath), 'csv', [
                // Inject a normalized header.
                CsvEncoder::HEADERS_KEY => $normalizedCsvHeader,
            ]);
        } catch (\UnexpectedValueException $e) {
            $results['errors'][] = ['message' => $e->getMessage()];

            return $results;
        }

        foreach ($content as $index => $collection) {
            $platform = PlatformEnum::Discogs;
            ++$results['total'];

            $requiredFields = ['title', 'artist', 'release_id'];
            $missingRequiredValues = [];

            foreach ($requiredFields as $field) {
                $value = $collection[$field] ?? null;

                if (null === $value || '' === trim((string) $value)) {
                    $missingRequiredValues[] = $field;
                }
            }

            if ([] !== $missingRequiredValues) {
                $results['errors'][] = [
                    'line'    => $index + 2,
                    'message' => sprintf('Missing required value(s): %s.', implode(', ', $missingRequiredValues)),
                ];

                continue;
            }

            try {
                $albumUuid             = UuidV7::v7();
                $externalReferenceUuid = UuidV7::v7();

                $albumDTO             = DiscogsAlbumImport::withData($collection);
                $externalReferenceDTO = DiscogsExternalReferenceImport::withData(
                    $collection
                );

                $existingAlbum = $this->externalReferenceReader->existsByOwnerPlatformExternalId(
                    $userUuid,
                    $platform->value,
                    $externalReferenceDTO->externalId
                );

                if ($existingAlbum) {
                    ++$results['skipped'];
                    continue;
                }

                $this->entityManager->beginTransaction();

                $album = new Album(
                    uuid: $albumUuid,
                    ownerUuid: $userUuid,
                    title: $albumDTO->title,
                    artist: $albumDTO->artist,
                    format: $albumDTO->format,
                    releaseYear: $albumDTO->releaseYear,
                    label: $albumDTO->label,
                );
                $this->albumWriter->save($album);

                $externalReference = new ExternalReference(
                    uuid: $externalReferenceUuid,
                    album: $album,
                    platform: $platform,
                    externalId: $externalReferenceDTO->externalId,
                    metadata: $externalReferenceDTO->metadata,
                );
                $this->externalReferenceWriter->save($externalReference);

                $this->entityManager->commit();
                ++$results['imported'];
            } catch (\Throwable $e) {
                if ($dbConnection->isTransactionActive()) {
                    $this->entityManager->rollback();
                }

                $results['errors'][] = [
                    'line'    => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
