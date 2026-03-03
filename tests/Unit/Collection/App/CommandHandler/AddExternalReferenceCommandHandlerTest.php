<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\AddExternalReferenceCommand;
use App\Collection\App\CommandHandler\AddExternalReferenceCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class AddExternalReferenceCommandHandlerTest extends TestCase
{
    #[Test]
    public function addExternalReferenceCommandHandlerSavesWithCorrectData(): void
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
            1992,
            'Trip Hop',
            'Ninja Tune',
            'https://google.com/cover.jpg',
        );

        $payload = [
            'albumUuid' => '019c2e97-8e0e-776c-bf55-76a2765e369d',
            'platform' => 'spotify',
            'externalId' => 'abc123',
            'metadata' => ['url' => 'https://open.spotify.com/album/abc123'],
        ];

        $command = AddExternalReferenceCommand::withData($uuid, $payload);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($this->equalTo($albumUuid))
            ->willReturn($album);

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ExternalReference $externalReference) use ($uuid, $album) {
                $this->assertSame($uuid, $externalReference->getUuid());
                $this->assertSame($album, $externalReference->getAlbum());
                $this->assertSame(PlatformEnum::Spotify->value, $externalReference->getPlatform());
                $this->assertSame('abc123', $externalReference->getExternalId());
                $this->assertSame(['url' => 'https://open.spotify.com/album/abc123'], $externalReference->getMetadata());
            });

        $handler = new AddExternalReferenceCommandHandler($mockAlbumReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function addExternalReferenceCommandHandlerWillThrowAlbumNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $albumUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $payload = [
            'albumUuid' => '019c2e97-8e0e-776c-bf55-76a2765e369d',
            'platform' => 'spotify',
            'externalId' => 'abc123',
            'metadata' => null,
        ];

        $command = AddExternalReferenceCommand::withData($uuid, $payload);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($this->equalTo($albumUuid))
            ->willThrowException(new AlbumNotFoundException());

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->never())
            ->method('save');

        $this->expectException(AlbumNotFoundException::class);

        $handler = new AddExternalReferenceCommandHandler($mockAlbumReader, $mockWriter);
        $handler($command);
    }
}
