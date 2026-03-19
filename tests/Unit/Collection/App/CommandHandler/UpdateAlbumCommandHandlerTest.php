<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\UpdateAlbumCommand;
use App\Collection\App\CommandHandler\UpdateAlbumCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

class UpdateAlbumCommandHandlerTest extends TestCase
{
    #[Test]
    public function updateAlbumCommandHandlerUpdatesAlbumWithCorrectData(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $existingAlbum = new Album(
            $uuid,
            $ownerUuid,
            'Old Title',
            'Old Artist',
            'CD',
            false,
            1990,
            'Old Genre',
            'Old Label',
            'https://old.com/cover.jpg',
        );

        $payload = [
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => '1992',
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://google.com/cover.jpg',
        ];

        $command = UpdateAlbumCommand::withData($uuid, $ownerUuid, false, $payload);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($existingAlbum);

        $mockAlbumWriter = $this->createMock(AlbumWriterInterface::class);
        $mockAlbumWriter
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Album $album) {
                $this->assertSame('Animal Magic', $album->getTitle());
                $this->assertSame('Bonobo', $album->getArtist());
                $this->assertSame(1992, $album->getReleaseYear());
                $this->assertSame('Vinyle', $album->getFormat());
                $this->assertFalse($album->isFavorite());
                $this->assertSame('Trip Hop', $album->getGenre());
                $this->assertSame('Ninja Tune', $album->getLabel());
                $this->assertSame('https://google.com/cover.jpg', $album->getCoverUrl());
                $this->assertInstanceOf(\DateTimeImmutable::class, $album->getUpdatedAt());
            });

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('info');

        $handler = new UpdateAlbumCommandHandler($mockAlbumReader, $mockAlbumWriter, $mockLogger);
        $handler($command);
    }

    #[Test]
    public function updateAlbumCommandHandlerAlbumWillThrowAlbumNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $payload = [
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => '1992',
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://google.com/cover.jpg',
        ];

        $command = UpdateAlbumCommand::withData($uuid, $ownerUuid, false, $payload);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willThrowException(new AlbumNotFoundException());

        $mockAlbumWriter = $this->createMock(AlbumWriterInterface::class);
        $mockAlbumWriter
            ->expects($this->never())
            ->method('save');

        $this->expectException(AlbumNotFoundException::class);

        $handler = new UpdateAlbumCommandHandler($mockAlbumReader, $mockAlbumWriter, $this->createStub(LoggerInterface::class));
        $handler($command);
    }
}
