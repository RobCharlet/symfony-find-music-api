<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumsByOwnerQuery;
use App\Collection\App\QueryHandler\FindAlbumsByOwnerQueryHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindAlbumsByOwnerQueryHandlerTest extends TestCase
{
    #[Test]
    public function findAlbumsByOwnerQueryHandlerRetrieveAlbumsWithOwnerUuid(): void
    {
        // Arrange
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $uuid2 = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d78');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $firstAlbum = new Album(
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

        $secondAlbum = new Album(
            $uuid2,
            $ownerUuid,
            'Days To Come',
            'Bonobo',
            'Vinyle',
            1999,
            'Trip Hop',
            'Ninja Tune',
            'https://google.com/cover2.jpg',
        );

        $query = FindAlbumsByOwnerQuery::withOwnerUuid($ownerUuid);
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findAllByOwnerUuid')
            ->willReturn([$firstAlbum, $secondAlbum]);

        // Act
        $handler = new FindAlbumsByOwnerQueryHandler($mockAlbumReader);
        $result = $handler($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame($firstAlbum, $result[0]);
        $this->assertSame($secondAlbum, $result[1]);
    }

    #[Test]
    public function findAlbumQueryHandlerWillReturnEmptyArray(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindAlbumsByOwnerQuery::withOwnerUuid($ownerUuid);
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findAllByOwnerUuid')
            ->willReturn([]);

        $handler = new FindAlbumsByOwnerQueryHandler($mockAlbumReader);
        $handler($query);
    }
}
