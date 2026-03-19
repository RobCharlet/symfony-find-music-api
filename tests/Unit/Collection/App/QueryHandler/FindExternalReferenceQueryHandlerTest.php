<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferenceQuery;
use App\Collection\App\QueryHandler\FindExternalReferenceQueryHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindExternalReferenceQueryHandlerTest extends TestCase
{
    #[Test]
    public function findExternalReferenceQueryHandlerRetrieveExternalReferenceWithUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
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
            'https://google.com/cover.jpg',
        );

        $existingExternalReference = new ExternalReference(
            $uuid,
            $album,
            PlatformEnum::Spotify,
            'abc123',
            ['url' => 'https://open.spotify.com/album/abc123']
        );

        $query = FindExternalReferenceQuery::withUuid($uuid, $ownerUuid, false);
        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->willReturn($existingExternalReference);

        $handler = new FindExternalReferenceQueryHandler($mockReader);
        $result = $handler($query);

        $this->assertSame($existingExternalReference, $result);
    }

    #[Test]
    public function findExternalReferenceQueryHandlerWillThrowExternalReferenceNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d59');

        $query = FindExternalReferenceQuery::withUuid($uuid, $ownerUuid, false);
        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->willThrowException(new ExternalReferenceNotFoundException());

        $this->expectException(ExternalReferenceNotFoundException::class);

        $handler = new FindExternalReferenceQueryHandler($mockReader);
        $handler($query);
    }
}
