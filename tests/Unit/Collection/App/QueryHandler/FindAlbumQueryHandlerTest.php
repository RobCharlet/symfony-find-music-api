<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumQuery;
use App\Collection\App\QueryHandler\FindAlbumQueryHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindAlbumQueryHandlerTest extends TestCase
{
    #[Test]
    public function findAlbumQueryHandlerRetrieveAlbumWithUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $existingAlbum = new Album(
            $uuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            1992,
            'Trip Hop',
            'Ninja Tune',
            'https://google.com/cover.jpg',
        );

        $query = FindAlbumQuery::withUuid($uuid, $ownerUuid, false);
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->willReturn($existingAlbum);

        $handler = new FindAlbumQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($existingAlbum, $result);
    }

    #[Test]
    public function findAlbumQueryHandlerWillThrowAnAlbumNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindAlbumQuery::withUuid($uuid, $ownerUuid, false);
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->willThrowException(new AlbumNotFoundException());

        $this->expectException(AlbumNotFoundException::class);

        $handler = new FindAlbumQueryHandler($mockAlbumReader);
        $handler($query);
    }
}
