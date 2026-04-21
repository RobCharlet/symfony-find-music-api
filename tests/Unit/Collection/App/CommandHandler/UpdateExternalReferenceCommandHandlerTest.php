<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\UpdateExternalReferenceCommand;
use App\Collection\App\CommandHandler\UpdateExternalReferenceCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UpdateExternalReferenceCommandHandlerTest extends TestCase
{
    #[Test]
    public function updateExternalReferenceCommandHandlerUpdatesWithCorrectData(): void
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
            'old-id',
            null
        );

        $payload = [
            'platform' => 'discogs',
            'externalId' => 'new-id',
            'metadata' => ['releaseId' => 12345],
        ];

        $command = UpdateExternalReferenceCommand::withData($uuid, $ownerUuid, false, $payload);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($existingExternalReference);

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (ExternalReference $externalReference) {
                $this->assertSame(PlatformEnum::Discogs, $externalReference->getPlatform());
                $this->assertSame('new-id', $externalReference->getExternalId());
                $this->assertSame(['releaseId' => 12345], $externalReference->getMetadata());
            });

        $handler = new UpdateExternalReferenceCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function updateExternalReferenceCommandHandlerWillThrowExternalReferenceNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $payload = [
            'platform' => 'discogs',
            'externalId' => 'new-id',
            'metadata' => null,
        ];

        $command = UpdateExternalReferenceCommand::withData($uuid, $ownerUuid, false, $payload);

        $mockReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findByUuid')
            ->with($uuid)
            ->willThrowException(new ExternalReferenceNotFoundException());

        $mockWriter = $this->createMock(ExternalReferenceWriterInterface::class);
        $mockWriter
            ->expects($this->never())
            ->method('save');

        $this->expectException(ExternalReferenceNotFoundException::class);

        $handler = new UpdateExternalReferenceCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }
}
