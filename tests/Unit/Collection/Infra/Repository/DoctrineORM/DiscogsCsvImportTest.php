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
    public function throwableInLoopLogsRowError(): void
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
