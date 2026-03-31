<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\FindCollectionByOwnerQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindCollectionByOwnerQueryTest extends TestCase
{
    #[Test]
    public function findCollectionByOwnerQueryIsCreatedWithOwnerUuid()
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = FindCollectionByOwnerQuery::withOwnerUuid(
            $ownerUuid,
            $requesterUuid,
            true,
        );

        $this->assertSame($ownerUuid, $query->ownerUuid);
        $this->assertSame($requesterUuid, $query->requesterUuid);
        $this->assertTrue($query->isAdmin);
    }
}
