<?php

namespace App\Collection\App\Command;

use Symfony\Component\Uid\Uuid;

class ImportCsvCommand
{
    public function __construct(
        public string $filePath,
        public Uuid $userUuid,
    ) {
    }

    public static function withFilePath(string $filePath, Uuid $userUuid): ImportCsvCommand
    {
        return new self(
            filePath: $filePath,
            userUuid: $userUuid
        );
    }
}
