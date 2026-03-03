<?php

namespace App\Collection\Domain;

use Symfony\Component\Uid\Uuid;

interface UuidGeneratorInterface
{
    public function generate(): Uuid;
}
