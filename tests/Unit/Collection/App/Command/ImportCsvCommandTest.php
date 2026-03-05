<?php

namespace App\Tests\Unit\Collection\App\Command;

use App\Collection\App\Command\ImportCsvCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class ImportCsvCommandTest extends TestCase
{
    #[Test]
    public function importCsvCommandIsCreatedWithPageAndLimit(): void
    {
        $filePath = '/testpath/file.csv';
        $userUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $command = ImportCsvCommand::withFilePath($filePath, $userUuid);

        $this->assertSame($filePath, $command->filePath);
        $this->assertSame('019c2e97-8e0e-776c-bf55-76a2765e369d', $command->userUuid->toString());

    }
}
