<?php

namespace App\Tests\Unit\Collection\App\CommandHandler;

use App\Collection\App\Command\EnrichAlbumFromDiscogsCommand;
use App\Collection\App\CommandHandler\EnrichAlbumFromDiscogsCommandHandler;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\DiscogsIdException;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use App\Collection\Domain\Repository\DiscogsApiClientInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\User\Domain\Repository\DiscogsCredentialsReaderInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class EnrichAlbumFromDiscogsCommandHandlerTest extends TestCase
{
    private const ALBUM_UUID = '019c2e97-4f81-75c5-8eca-ec2ff86f7d56';
    private const OWNER_UUID = '019c2e97-8e0e-776c-bf55-76a2765e369d';
    private const EXTERNAL_REF_UUID = '019c2e97-8e0e-776c-bf55-76a2765e369e';

    #[Test]
    public function enrichesAlbumWithDiscogsReleaseData(): void
    {
        $albumUuid = UuidV7::fromString(self::ALBUM_UUID);
        $ownerUuid = UuidV7::fromString(self::OWNER_UUID);

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
        );

        $externalReference = new ExternalReference(
            UuidV7::fromString(self::EXTERNAL_REF_UUID),
            $album,
            PlatformEnum::Discogs,
            '12345',
        );

        $token = DiscogsAccessToken::fromString('xYzDiscogsToken');

        $albumReader = $this->createMock(AlbumReaderInterface::class);
        $albumReader->expects($this->once())
            ->method('findByUuid')
            ->with($albumUuid)
            ->willReturn($album);

        $externalReferenceReader = $this->createMock(ExternalReferenceReaderInterface::class);
        $externalReferenceReader->expects($this->once())
            ->method('findAllByAlbumUuid')
            ->with($albumUuid)
            ->willReturn([$externalReference]);

        $credentialsReader = $this->createMock(DiscogsCredentialsReaderInterface::class);
        $credentialsReader->expects($this->once())
            ->method('getDecryptedToken')
            ->with($ownerUuid)
            ->willReturn($token);

        $apiClient = $this->createMock(DiscogsApiClientInterface::class);
        $apiClient->expects($this->once())
            ->method('fetchRelease')
            ->with('12345', $token)
            ->willReturn([
                'images' => [['uri' => 'https://img.discogs.com/animal-magic.jpg']],
                'genres' => ['Electronic'],
                'labels' => [['name' => 'Tru Thoughts']],
                'year' => 1999,
            ]);

        $albumWriter = $this->createMock(AlbumWriterInterface::class);
        $albumWriter->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Album $saved) {
                $this->assertSame('https://img.discogs.com/animal-magic.jpg', $saved->getCoverUrl());
                $this->assertSame('Electronic', $saved->getGenre());
                $this->assertSame('Tru Thoughts', $saved->getLabel());
                $this->assertSame(1999, $saved->getReleaseYear());
            });

        $handler = new EnrichAlbumFromDiscogsCommandHandler(
            $albumReader,
            $albumWriter,
            $apiClient,
            $credentialsReader,
            $externalReferenceReader,
        );

        $handler(EnrichAlbumFromDiscogsCommand::withAlbumUuid($albumUuid, $ownerUuid));
    }

    #[Test]
    public function throwsWhenUserIsNotOwner(): void
    {
        $albumUuid = UuidV7::fromString(self::ALBUM_UUID);
        $ownerUuid = UuidV7::fromString(self::OWNER_UUID);
        $otherUserUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e3700');

        $album = new Album($albumUuid, $ownerUuid, 'Animal Magic', 'Bonobo', 'Vinyle');

        $albumReader = $this->createStub(AlbumReaderInterface::class);
        $albumReader->method('findByUuid')->willReturn($album);

        $albumWriter = $this->createMock(AlbumWriterInterface::class);
        $albumWriter->expects($this->never())->method('save');

        $apiClient = $this->createMock(DiscogsApiClientInterface::class);
        $apiClient->expects($this->never())->method('fetchRelease');

        $handler = new EnrichAlbumFromDiscogsCommandHandler(
            $albumReader,
            $albumWriter,
            $apiClient,
            $this->createStub(DiscogsCredentialsReaderInterface::class),
            $this->createStub(ExternalReferenceReaderInterface::class),
        );

        $this->expectException(OwnershipForbiddenException::class);

        $handler(EnrichAlbumFromDiscogsCommand::withAlbumUuid($albumUuid, $otherUserUuid));
    }

    #[Test]
    public function throwsWhenAlbumHasNoExternalReferences(): void
    {
        $albumUuid = UuidV7::fromString(self::ALBUM_UUID);
        $ownerUuid = UuidV7::fromString(self::OWNER_UUID);

        $album = new Album($albumUuid, $ownerUuid, 'Animal Magic', 'Bonobo', 'Vinyle');

        $albumReader = $this->createStub(AlbumReaderInterface::class);
        $albumReader->method('findByUuid')->willReturn($album);

        $externalReferenceReader = $this->createStub(ExternalReferenceReaderInterface::class);
        $externalReferenceReader->method('findAllByAlbumUuid')->willReturn([]);

        $albumWriter = $this->createMock(AlbumWriterInterface::class);
        $albumWriter->expects($this->never())->method('save');

        $handler = new EnrichAlbumFromDiscogsCommandHandler(
            $albumReader,
            $albumWriter,
            $this->createStub(DiscogsApiClientInterface::class),
            $this->createStub(DiscogsCredentialsReaderInterface::class),
            $externalReferenceReader,
        );

        $this->expectException(ExternalReferenceNotFoundException::class);

        $handler(EnrichAlbumFromDiscogsCommand::withAlbumUuid($albumUuid, $ownerUuid));
    }

    #[Test]
    public function throwsWhenAlbumHasNoDiscogsReference(): void
    {
        $albumUuid = UuidV7::fromString(self::ALBUM_UUID);
        $ownerUuid = UuidV7::fromString(self::OWNER_UUID);

        $album = new Album($albumUuid, $ownerUuid, 'Animal Magic', 'Bonobo', 'Vinyle');

        $spotifyReference = new ExternalReference(
            UuidV7::fromString(self::EXTERNAL_REF_UUID),
            $album,
            PlatformEnum::Spotify,
            'spotify-id',
        );

        $albumReader = $this->createStub(AlbumReaderInterface::class);
        $albumReader->method('findByUuid')->willReturn($album);

        $externalReferenceReader = $this->createStub(ExternalReferenceReaderInterface::class);
        $externalReferenceReader->method('findAllByAlbumUuid')->willReturn([$spotifyReference]);

        $albumWriter = $this->createMock(AlbumWriterInterface::class);
        $albumWriter->expects($this->never())->method('save');

        $apiClient = $this->createMock(DiscogsApiClientInterface::class);
        $apiClient->expects($this->never())->method('fetchRelease');

        $handler = new EnrichAlbumFromDiscogsCommandHandler(
            $albumReader,
            $albumWriter,
            $apiClient,
            $this->createStub(DiscogsCredentialsReaderInterface::class),
            $externalReferenceReader,
        );

        $this->expectException(DiscogsIdException::class);

        $handler(EnrichAlbumFromDiscogsCommand::withAlbumUuid($albumUuid, $ownerUuid));
    }
}
