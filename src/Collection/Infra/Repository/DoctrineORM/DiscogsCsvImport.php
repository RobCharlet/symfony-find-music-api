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
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $logger,
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

            $this->logger->warning('import.missing_columns', [
                'columns' => array_values($missingColumns),
            ]);

            return $results;
        }

        // Decode CSV
        try {
            $content = $decoder->decode(file_get_contents($filePath), 'csv', [
                // Inject a normalized header.
                CsvEncoder::HEADERS_KEY => $normalizedCsvHeader,
            ]);
        } catch (\UnexpectedValueException $e) {
            $this->logger->warning('import.decode_error', [
                'message' => $e->getMessage(),
            ]);
            $results['errors'][] = ['message' => $e->getMessage()];

            return $results;
        }

        foreach ($content as $index => $csvRow) {
            $platform = PlatformEnum::Discogs;
            ++$results['total'];

            $requiredFields = ['title', 'artist', 'release_id'];
            $missingRequiredValues = [];

            foreach ($requiredFields as $field) {
                $value = $csvRow[$field] ?? null;

                if (null === $value || '' === trim((string) $value)) {
                    $missingRequiredValues[] = $field;
                }
            }

            if ([] !== $missingRequiredValues) {
                $message = sprintf('Missing required value(s): %s.', implode(', ', $missingRequiredValues));

                $this->logger->warning('import.row_error', [
                    'line'    => $index + 2,
                    'message' => $message,
                ]);
                $results['errors'][] = [
                    'line'    => $index + 2,
                    'message' => $message,
                ];

                continue;
            }

            try {
                $albumUuid             = UuidV7::v7();
                $externalReferenceUuid = UuidV7::v7();

                $albumDTO             = DiscogsAlbumImport::withData($csvRow);
                $externalReferenceDTO = DiscogsExternalReferenceImport::withData(
                    $csvRow
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

                $line = $index + 2;
                $this->logger->warning('import.row_error', [
                    'line'    => $line,
                    'message' => $e->getMessage(),
                ]);
                $results['errors'][] = [
                    'line'    => $line,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
