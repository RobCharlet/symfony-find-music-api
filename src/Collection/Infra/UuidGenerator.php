<?php

namespace App\Collection\Infra;

use App\Collection\Domain\UuidGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

class UuidGenerator implements UuidGeneratorInterface
{
    public function generate(): Uuid
    {
        return UuidV7::v7();
    }
}
