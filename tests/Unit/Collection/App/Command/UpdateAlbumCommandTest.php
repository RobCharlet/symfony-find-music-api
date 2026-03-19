<?php

namespace App\Tests\Unit\Collection\App\Command;

use App\Collection\App\Command\UpdateAlbumCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UpdateAlbumCommandTest extends TestCase
{
    #[Test]
    public function updateAlbumCommandIsCreatedFromPayload(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $payload = [
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => '1992',
            'format' => 'Vinyle',
            'isFavorite' => true,
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://google.com/cover.jpg',
        ];

        $command = UpdateAlbumCommand::withData($uuid, $ownerUuid, false, $payload);

        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $command->uuid->toString());
        $this->assertSame('Animal Magic', $command->title);
        $this->assertSame('Bonobo', $command->artist);
        $this->assertSame('1992', $command->releaseYear);
        $this->assertSame('Vinyle', $command->format);
        $this->assertTrue($command->isFavorite);
        $this->assertSame('Trip Hop', $command->genre);
        $this->assertSame('Ninja Tune', $command->label);
        $this->assertSame('https://google.com/cover.jpg', $command->coverUrl);
    }

    #[Test]
    public function updateAlbumCommandSetsNullableFieldsToNullWhenMissing(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $payload = [
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => '1992',
            'format' => 'Vinyle',
            'isFavorite' => true,
        ];

        $command = UpdateAlbumCommand::withData($uuid, $ownerUuid, false, $payload);

        $this->assertNull($command->genre);
        $this->assertNull($command->label);
        $this->assertNull($command->coverUrl);
    }
}
