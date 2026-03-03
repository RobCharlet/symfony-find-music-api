<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\FindAlbumsByOwnerQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindAlbumsByOwnerQueryTest extends TestCase
{
    #[Test]
    public function findAlbumQueryIsCreatedWithUuid()
    {
        $uuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindAlbumsByOwnerQuery::withOwnerUuid($uuid);

        $this->assertSame($uuid, $query->ownerUuid);
    }
}
