<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\ImportCsvCommand;
use App\Collection\App\CommandHandler\ImportCsvCommandHandler;
use App\Collection\Domain\Repository\CsvImportInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

class ImportCsvCommandHandlerTest extends TestCase
{
    #[Test]
    public function importCsvCommandHandlerImportCollection(): void
    {
        $filePath = '/path/test.csv';
        $userUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $command = ImportCsvCommand::withFilePath($filePath, $userUuid);
        $mockCsvImport = $this->createMock(CsvImportInterface::class);

        $mockCsvImport->expects(
            $this->once()
        )->method('import')
        ->with($filePath, $userUuid)
        ->willReturn(['total' => 5, 'imported' => 4, 'skipped' => 1, 'errors' => []]);

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))->method('info');

        $handler = new ImportCsvCommandHandler($mockCsvImport, $mockLogger);
        $handler($command);
    }
}
