<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\DeleteExternalReferenceCommand;
use App\Collection\App\CommandHandler\DeleteExternalReferenceCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class DeleteExternalReferenceCommandHandlerTest extends TestCase
{
    #[Test]
    public function deleteExternalReferenceCommandHandlerDeletesExternalReference(): void
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
            null
        );

        $command = DeleteExternalReferenceCommand::withUuid($uuid, $ownerUuid, false);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($existingExternalReference);

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('delete')
            ->with($existingExternalReference);

        $handler = new DeleteExternalReferenceCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function deleteExternalReferenceCommandHandlerWillThrowExternalReferenceNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $command = DeleteExternalReferenceCommand::withUuid($uuid, $ownerUuid, false);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willThrowException(new ExternalReferenceNotFoundException());

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->never())
            ->method('delete');

        $this->expectException(ExternalReferenceNotFoundException::class);

        $handler = new DeleteExternalReferenceCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }
}
