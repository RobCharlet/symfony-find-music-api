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
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = FindAlbumsByOwnerQuery::withOwnerUuid(
            $uuid,
            $requesterUuid,
            true,
            1,
            50,
            'title',
            'desc',
            'rock'
        );

        $this->assertSame($uuid, $query->ownerUuid);
        $this->assertSame($requesterUuid, $query->requesterUuid);
        $this->assertTrue($query->isAdmin);
        $this->assertSame(1, $query->page);
        $this->assertSame(50, $query->limit);
        $this->assertSame('title', $query->sortBy);
        $this->assertSame('desc', $query->sortOrder);
        $this->assertSame('rock', $query->genre);
    }
}
