<?php

namespace App\Tests\Unit\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Repository\AlbumWriterInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use App\Collection\Infra\Repository\DoctrineORM\DiscogsCsvImport;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

class DiscogsCsvImportTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir();
    }

    private function createImporter(LoggerInterface $logger): DiscogsCsvImport
    {
        $connection = $this->createStub(Connection::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        return new DiscogsCsvImport(
            $this->createStub(AlbumWriterInterface::class),
            $entityManager,
            $this->createStub(ExternalReferenceReaderInterface::class),
            $this->createStub(ExternalReferenceWriterInterface::class),
            $logger,
        );
    }

    #[Test]
    public function missingColumnsLogsWarning(): void
    {
        $csv = "title,artist\n\"Paranoid\",\"Black Sabbath\"\n";
        $file = $this->tmpDir.'/missing_columns.csv';
        file_put_contents($file, $csv);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('import.missing_columns', $this->callback(
                fn (array $context) => isset($context['columns'])
                    && in_array('release_id', $context['columns'], true)
            ));

        $importer = $this->createImporter($logger);
        $result = $importer->import($file, UuidV7::v7());

        $this->assertNotEmpty($result['errors']);

        unlink($file);
    }

    #[Test]
    public function missingRequiredValuesLogsRowError(): void
    {
        $csv = "title,artist,released,format,label,release_id\n\"\",\"Black Sabbath\",\"1970\",\"Vinyl\",\"Vertigo\",\"12345\"\n";
        $file = $this->tmpDir.'/missing_values.csv';
        file_put_contents($file, $csv);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('import.row_error', $this->callback(
                fn (array $context) => 2 === $context['line']
                    && str_contains($context['message'], 'title')
            ));

        $importer = $this->createImporter($logger);
        $result = $importer->import($file, UuidV7::v7());

        $this->assertNotEmpty($result['errors']);
        $this->assertSame(2, $result['errors'][0]['line']);

        unlink($file);
    }

    #[Test]
    public function discogsHeadersWithMixedCaseAreNormalized(): void
    {
        $csv = "Catalog#,Artist,Title,Label,Format,Rating,Released,release_id,CollectionFolder,Date Added,Collection Media Condition,Collection Sleeve Condition,Collection Notes\n"
            ."piasr 210 lp,I Am Kloot,Sky At Night,[PIAS] Recordings,\"LP, Album\",,2010,2596660,Uncategorized,2026-01-19 11:01:49,Near Mint (NM or M-),Near Mint (NM or M-),\n";
        $file = $this->tmpDir.'/discogs_real_headers.csv';
        file_put_contents($file, $csv);

        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $externalRefReader = $this->createStub(ExternalReferenceReaderInterface::class);
        $externalRefReader->method('existsByOwnerPlatformExternalId')->willReturn(false);

        $albumWriter = $this->createMock(AlbumWriterInterface::class);
        $albumWriter->expects($this->once())->method('save');

        $importer = new DiscogsCsvImport(
            $albumWriter,
            $entityManager,
            $externalRefReader,
            $this->createStub(ExternalReferenceWriterInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $result = $importer->import($file, UuidV7::v7());

        $this->assertSame(1, $result['imported']);
        $this->assertEmpty($result['errors']);

        unlink($file);
    }

    #[Test]
    public function fopenFailureThrowsRuntimeException(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $importer = $this->createImporter($logger);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open CSV file.');

        $importer->import('/nonexistent/path/to/file.csv', UuidV7::v7());
    }

    #[Test]
    public function errorInLoopBubblesUp(): void
    {
        $csv = "title,artist,released,format,label,release_id\n\"Paranoid\",\"Black Sabbath\",\"1970\",\"Vinyl\",\"Vertigo\",\"12345\"\n";
        $file = $this->tmpDir.'/error_bubble.csv';
        file_put_contents($file, $csv);

        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $externalRefReader = $this->createStub(ExternalReferenceReaderInterface::class);
        $externalRefReader->method('existsByOwnerPlatformExternalId')->willReturn(false);

        $albumWriter = $this->createStub(AlbumWriterInterface::class);
        $albumWriter->method('save')->willThrowException(new \TypeError('Unexpected null value'));

        $importer = new DiscogsCsvImport(
            $albumWriter,
            $entityManager,
            $externalRefReader,
            $this->createStub(ExternalReferenceWriterInterface::class),
            $this->createStub(LoggerInterface::class),
        );

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Unexpected null value');

        try {
            $importer->import($file, UuidV7::v7());
        } finally {
            unlink($file);
        }
    }

    #[Test]
    public function exceptionInLoopLogsRowError(): void
    {
        $csv = "title,artist,released,format,label,release_id\n\"Paranoid\",\"Black Sabbath\",\"1970\",\"Vinyl\",\"Vertigo\",\"12345\"\n";
        $file = $this->tmpDir.'/throwable.csv';
        file_put_contents($file, $csv);

        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $externalRefReader = $this->createStub(ExternalReferenceReaderInterface::class);
        $externalRefReader->method('existsByOwnerPlatformExternalId')->willReturn(false);

        $albumWriter = $this->createStub(AlbumWriterInterface::class);
        $albumWriter->method('save')->willThrowException(new \RuntimeException('DB connection lost'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('import.row_error', $this->callback(
                fn (array $context) => 2 === $context['line']
                    && 'DB connection lost' === $context['message']
            ));

        $importer = new DiscogsCsvImport(
            $albumWriter,
            $entityManager,
            $externalRefReader,
            $this->createStub(ExternalReferenceWriterInterface::class),
            $logger,
        );

        $result = $importer->import($file, UuidV7::v7());

        $this->assertNotEmpty($result['errors']);
        $this->assertSame('DB connection lost', $result['errors'][0]['message']);

        unlink($file);
    }
}
