<?php

namespace App\Tests\Unit\Collection\App\Command;

use App\Collection\App\Command\AddExternalReferenceCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class AddExternalReferenceCommandTest extends TestCase
{
    #[Test]
    public function addExternalReferenceCommandIsCreatedFromPayload(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $payload = [
            'albumUuid' => '019c2e97-8e0e-776c-bf55-76a2765e369d',
            'platform' => 'spotify',
            'externalId' => 'abc123',
            'metadata' => ['url' => 'https://open.spotify.com/album/abc123'],
        ];

        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $command = AddExternalReferenceCommand::withData($uuid, $ownerUuid, false, $payload);

        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $command->uuid->toString());
        $this->assertSame('019c2e97-8e0e-776c-bf55-76a2765e369d', $command->albumUuid);
        $this->assertSame('spotify', $command->platform);
        $this->assertSame('abc123', $command->externalId);
        $this->assertSame(['url' => 'https://open.spotify.com/album/abc123'], $command->metadata);
    }
}
