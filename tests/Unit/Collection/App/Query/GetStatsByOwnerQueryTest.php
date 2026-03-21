<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\GetStatsByOwnerQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class GetStatsByOwnerQueryTest extends TestCase
{
    #[Test]
    public function getStatsByOwnerQueryIsCreatedWithOwnerUuid()
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $userUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = GetStatsByOwnerQuery::withOwnerUuid(
            $ownerUuid,
            $userUuid,
            true,
        );

        $this->assertSame($ownerUuid, $query->ownerUuid);
        $this->assertSame($userUuid, $query->userUuid);
        $this->assertTrue($query->isAdmin);
    }
}
