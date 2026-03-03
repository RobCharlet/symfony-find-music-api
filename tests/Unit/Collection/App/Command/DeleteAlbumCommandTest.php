<?php

namespace App\Tests\Unit\Collection\App\Command;

use App\Collection\App\Command\DeleteAlbumCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class DeleteAlbumCommandTest extends TestCase
{
    #[Test]
    public function deleteAlbumCommandIsCreatedFromUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $command = DeleteAlbumCommand::withUuid($uuid, $ownerUuid, false);

        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $command->uuid->toString());
        $this->assertSame('019c2e97-8e0e-776c-bf55-76a2765e369d', $command->ownerUuid->toString());
        $this->assertSame(false, $command->isAdmin);
    }
}
