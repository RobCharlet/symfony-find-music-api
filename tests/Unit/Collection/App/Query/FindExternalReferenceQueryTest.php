<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\FindExternalReferenceQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindExternalReferenceQueryTest extends TestCase
{
    #[Test]
    public function findExternalReferenceQueryIsCreatedWithUuid()
    {
        $uuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindExternalReferenceQuery::withUuid($uuid, $ownerUuid, false);

        $this->assertSame($uuid, $query->uuid);
    }
}
