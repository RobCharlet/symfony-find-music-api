<?php

namespace App\Tests\Unit\Collection\Domain;

use App\Collection\Domain\Album;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class ExternalReferenceTest extends TestCase
{
    #[Test]
    public function getExternalReferenceContent(): void
    {
        $albumUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $extRefUuid = UuidV7::fromString('019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f');

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            false,
            2000,
            'Trip Hop',
            'Ninja Tune',
            null,
        );

        $metadata = ['url' => 'https://open.spotify.com/album/123'];

        $externalReference = new ExternalReference(
            $extRefUuid,
            $album,
            PlatformEnum::Spotify,
            'spotify-123',
            $metadata,
        );

        $this->assertSame('019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f', $externalReference->getUuid()->toString());
        $this->assertSame($album, $externalReference->getAlbum());
        $this->assertSame(PlatformEnum::Spotify, $externalReference->getPlatform());
        $this->assertSame('spotify-123', $externalReference->getExternalId());
        $this->assertSame($metadata, $externalReference->getMetadata());
    }

    #[Test]
    public function updateExternalReference(): void
    {
        $albumUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $extRefUuid = UuidV7::fromString('019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f');

        $album = new Album(
            $albumUuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            false,
            2000,
            null,
            null,
            null,
        );

        $externalReference = new ExternalReference(
            $extRefUuid,
            $album,
            PlatformEnum::Spotify,
            'spotify-123',
            ['url' => 'https://open.spotify.com/album/123'],
        );

        $newMetadata = ['url' => 'https://www.discogs.com/release/456'];

        $externalReference->update(
            PlatformEnum::Discogs,
            'discogs-456',
            $newMetadata,
        );

        $this->assertSame(PlatformEnum::Discogs, $externalReference->getPlatform());
        $this->assertSame('discogs-456', $externalReference->getExternalId());
        $this->assertSame($newMetadata, $externalReference->getMetadata());
        $this->assertSame('019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f', $externalReference->getUuid()->toString());
        $this->assertSame($album, $externalReference->getAlbum());
    }
}
