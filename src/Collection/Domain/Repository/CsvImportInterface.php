<?php

namespace App\Collection\Domain\Repository;

use Symfony\Component\Uid\Uuid;

interface CsvImportInterface
{
    public function import(string $filePath, Uuid $userUuid): array;
}
