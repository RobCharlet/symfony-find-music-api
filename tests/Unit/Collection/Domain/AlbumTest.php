<?php

namespace App\Tests\Unit\Collection\Domain;

use App\Collection\Domain\Album;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class AlbumTest extends TestCase
{
    #[Test]
    public function getAlbumContent(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $album = new Album(
            $uuid,
            $ownerUuid,
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            true,
            2000,
            'Trip Hop',
            'Ninja Tune',
            'https://example.com/cover.jpg',
        );

        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $album->getUuid()->toString());
        $this->assertSame('019c2e97-8e0e-776c-bf55-76a2765e369d', $album->getOwnerUuid()->toString());
        $this->assertSame('Animal Magic', $album->getTitle());
        $this->assertSame('Bonobo', $album->getArtist());
        $this->assertSame(2000, $album->getReleaseYear());
        $this->assertSame('Vinyle', $album->getFormat());
        $this->assertTrue($album->isFavorite());
        $this->assertSame('Trip Hop', $album->getGenre());
        $this->assertSame('Ninja Tune', $album->getLabel());
        $this->assertSame('https://example.com/cover.jpg', $album->getCoverUrl());
        $this->assertInstanceOf(\DateTimeImmutable::class, $album->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $album->getUpdatedAt());
    }

    #[Test]
    public function albumDefaultsIsFavoriteToFalse(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $album = new Album(
            $uuid,
            $ownerUuid,
            'Black Sands',
            'Bonobo',
            'Vinyle',
        );

        $this->assertFalse($album->isFavorite());
    }

    #[Test]
    public function updateAlbum(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $album = new Album(
            $uuid,
            $ownerUuid,
            'Old Title',
            'Old Artist',
            'CD',
            false,
            1990,
            'Rock',
            'Old Label',
            'https://example.com/old.jpg',
        );

        $oldUpdatedAt = $album->getUpdatedAt();

        $album->update(
            'Animal Magic',
            'Bonobo',
            'Vinyle',
            true,
            2000,
            'Trip Hop',
            'Ninja Tune',
            'https://example.com/cover.jpg',
        );

        $this->assertSame('Animal Magic', $album->getTitle());
        $this->assertSame('Bonobo', $album->getArtist());
        $this->assertSame(2000, $album->getReleaseYear());
        $this->assertSame('Vinyle', $album->getFormat());
        $this->assertTrue($album->isFavorite());
        $this->assertSame('Trip Hop', $album->getGenre());
        $this->assertSame('Ninja Tune', $album->getLabel());
        $this->assertSame('https://example.com/cover.jpg', $album->getCoverUrl());
        $this->assertGreaterThanOrEqual($oldUpdatedAt, $album->getUpdatedAt());
        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $album->getUuid()->toString());
        $this->assertSame('019c2e97-8e0e-776c-bf55-76a2765e369d', $album->getOwnerUuid()->toString());
    }
}
