<?php

namespace App\Tests\Unit\Collection\App\Query;

use App\Collection\App\Query\FindExternalReferencesByAlbumQuery;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindExternalReferencesByAlbumQueryTest extends TestCase
{
    #[Test]
    public function findExternalReferencesByAlbumQueryIsCreatedWithAlbumUuid()
    {
        $albumUuid     = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $query = FindExternalReferencesByAlbumQuery::withAlbumUuid($albumUuid, $requesterUuid, false);

        $this->assertSame($albumUuid, $query->albumUuid);
    }
}
