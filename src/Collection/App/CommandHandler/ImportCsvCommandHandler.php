<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\ImportCsvCommand;
use App\Collection\Domain\Repository\CsvImportInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ImportCsvCommandHandler
{
    public function __construct(
        private CsvImportInterface $csvImport,
    ) {
    }

    public function __invoke(ImportCsvCommand $command): array
    {
        $filePath = $command->filePath;
        $userUuid = $command->userUuid;

        return $this->csvImport->import($filePath, $userUuid);
    }
}
