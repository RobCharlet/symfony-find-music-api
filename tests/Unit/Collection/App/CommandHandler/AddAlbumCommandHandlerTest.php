<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\AddAlbumCommand;
use App\Collection\App\CommandHandler\AddAlbumCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

class AddAlbumCommandHandlerTest extends TestCase
{
    #[Test]
    public function addAlbumCommandHandlerSavesAlbumWithCorrectData(): void
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

        $command = AddAlbumCommand::withData($uuid, $ownerUuid, $payload);

        $mockAlbumWriter = $this->createMock(AlbumWriterInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $mockAlbumWriter
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Album $album) use ($uuid, $ownerUuid) {
                $this->assertSame($uuid, $album->getUuid());
                $this->assertSame($ownerUuid, $album->getOwnerUuid());
                $this->assertSame('Animal Magic', $album->getTitle());
                $this->assertSame('Bonobo', $album->getArtist());
                $this->assertSame(1992, $album->getReleaseYear());
                $this->assertSame('Vinyle', $album->getFormat());
                $this->assertSame('Trip Hop', $album->getGenre());
                $this->assertSame('Ninja Tune', $album->getLabel());
                $this->assertFalse($album->isFavorite());
                $this->assertSame('https://google.com/cover.jpg', $album->getCoverUrl());
                $this->assertInstanceOf(\DateTimeImmutable::class, $album->getCreatedAt());
                $this->assertInstanceOf(\DateTimeImmutable::class, $album->getUpdatedAt());
            });

        $mockLogger->expects($this->once())
            ->method('info')
        ;

        $handler = new AddAlbumCommandHandler($mockAlbumWriter, $mockLogger);
        $handler($command);
    }
}
