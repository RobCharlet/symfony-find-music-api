<?php

namespace App\Shared\Domain;

use Symfony\Component\Uid\Uuid;

interface UuidAwareUserInterface
{
    public function getUuid(): Uuid;
}
