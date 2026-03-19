<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferencesByAlbumQuery;
use App\Collection\App\QueryHandler\FindExternalReferencesByAlbumQueryHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindExternalReferencesByAlbumQueryHandlerTest extends TestCase
{
    #[Test]
    public function ownerCanRetrieveExternalReferences(): void
    {
        $uuid1     = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $uuid2     = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d78');
        $albumUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            false,
            1992,
            'Trip Hop',
            'Ninja Tune',
            'https://google.com/cover.jpg'
        );

        $firstRef  = new ExternalReference($uuid1, $album, PlatformEnum::Spotify, 'abc123', null);
        $secondRef = new ExternalReference($uuid2, $album, PlatformEnum::Discogs, 'xyz789', ['releaseId' => 12345]);

        $query = FindExternalReferencesByAlbumQuery::withAlbumUuid($albumUuid, $ownerUuid, false);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader->expects($this->once())->method('findByUuid')->with($albumUuid)->willReturn($album);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader->expects($this->once())->method('findAllByAlbumUuid')->willReturn([$firstRef, $secondRef]);

        $handler = new FindExternalReferencesByAlbumQueryHandler($mockAlbumReader, $mockReader);
        $result  = $handler($query);

        $this->assertCount(2, $result);
        $this->assertSame($firstRef, $result[0]);
        $this->assertSame($secondRef, $result[1]);
    }

    #[Test]
    public function nonOwnerCannotRetrieveExternalReferences(): void
    {
        $albumUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');
        $otherUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369f');

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            false,
            1992,
            'Trip Hop',
            'Ninja Tune',
            null
        );

        $query = FindExternalReferencesByAlbumQuery::withAlbumUuid($albumUuid, $otherUuid, false);

        $mockAlbumReader = $this->createStub(AlbumReaderInterface::class);
        $mockAlbumReader->method('findByUuid')->willReturn($album);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader->expects($this->never())->method('findAllByAlbumUuid');

        $this->expectException(OwnershipForbiddenException::class);

        $handler = new FindExternalReferencesByAlbumQueryHandler($mockAlbumReader, $mockReader);
        $handler($query);
    }

    #[Test]
    public function adminCanRetrieveExternalReferencesFromAnyAlbum(): void
    {
        $albumUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');
        $adminUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369f');

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            false,
            1992,
            'Trip Hop',
            'Ninja Tune',
            null
        );

        $query = FindExternalReferencesByAlbumQuery::withAlbumUuid($albumUuid, $adminUuid, true);

        $mockAlbumReader = $this->createStub(AlbumReaderInterface::class);
        $mockAlbumReader->method('findByUuid')->willReturn($album);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader->expects($this->once())->method('findAllByAlbumUuid')->willReturn([]);

        $handler = new FindExternalReferencesByAlbumQueryHandler($mockAlbumReader, $mockReader);
        $result  = $handler($query);

        $this->assertCount(0, $result);
    }
}
