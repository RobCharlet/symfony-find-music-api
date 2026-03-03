<?php

namespace App\Tests\Unit\Collection\App\Command;

use App\Collection\App\Command\UpdateExternalReferenceCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UpdateExternalReferenceCommandTest extends TestCase
{
    #[Test]
    public function updateExternalReferenceCommandIsCreatedFromPayload(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $payload = [
            'platform' => 'discogs',
            'externalId' => 'xyz789',
            'metadata' => ['releaseId' => 12345],
        ];

        $command = UpdateExternalReferenceCommand::withData($uuid, $ownerUuid, false, $payload);

        $this->assertSame('019c2e97-4f81-75c5-8eca-ec2ff86f7d56', $command->uuid->toString());
        $this->assertSame('discogs', $command->platform);
        $this->assertSame('xyz789', $command->externalId);
        $this->assertSame(['releaseId' => 12345], $command->metadata);
    }
}
