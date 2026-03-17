<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\ImportCsvCommand;
use App\Collection\Domain\Repository\CsvImportInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportCsvCommandHandler
{
    public function __construct(
        private CsvImportInterface $csvImport,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ImportCsvCommand $command): array
    {
        $filePath = $command->filePath;
        $userUuid = $command->userUuid;

        $this->logger->info('import.started', ['owner' => $userUuid]);

        $results = $this->csvImport->import($filePath, $userUuid);

        $this->logger->info('import.completed', [
            'owner'       => $userUuid,
            'total'       => $results['total'],
            'imported'    => $results['imported'],
            'skipped'     => $results['skipped'],
            'error_count' => count($results['errors']),
        ]);

        return $results;
    }
}
