<?php

namespace App\Collection\Domain\Repository;

use App\Collection\Domain\ExternalReference;

interface ExternalReferenceWriterInterface
{
    public function save(ExternalReference $externalReference): void;

    public function delete(ExternalReference $externalReference): void;
}
