<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\DeleteAlbumCommand;
use App\Collection\App\CommandHandler\DeleteAlbumCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\UuidV7;

class DeleteAlbumCommandHandlerTest extends TestCase
{
    #[Test]
    public function deleteAlbumCommandHandlerDeletesAlbum(): void
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

        $command = DeleteAlbumCommand::withUuid($uuid, $ownerUuid, false);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($existingAlbum);

        $mockAlbumWriter = $this->createMock(AlbumWriterInterface::class);
        $mockAlbumWriter
            ->expects($this->once())
            ->method('delete')
            ->with($existingAlbum);

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('info');

        $handler = new DeleteAlbumCommandHandler($mockAlbumReader, $mockAlbumWriter, $mockLogger);
        $handler($command);
    }

    #[Test]
    public function deleteAlbumCommandHandlerAlbumWillThrowAlbumNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $command = DeleteAlbumCommand::withUuid($uuid, $ownerUuid, false);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willThrowException(new AlbumNotFoundException());

        $mockAlbumWriter = $this->createMock(AlbumWriterInterface::class);
        $mockAlbumWriter
            ->expects($this->never())
            ->method('delete');

        $this->expectException(AlbumNotFoundException::class);

        $handler = new DeleteAlbumCommandHandler($mockAlbumReader, $mockAlbumWriter, $this->createStub(LoggerInterface::class));
        $handler($command);
    }
}
