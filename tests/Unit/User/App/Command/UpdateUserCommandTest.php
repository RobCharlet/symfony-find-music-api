<?php

namespace App\Tests\Unit\User\App\Command;

use App\User\App\Command\UpdateUserCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UpdateUserCommandTest extends TestCase
{
    #[Test]
    public function updateUserCommandIsCreatedFromPayload(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = UpdateUserCommand::withData(
            $uuid,
            'john@example.com',
            'new_password',
            ['ROLE_ADMIN'],
        );

        $this->assertSame($uuid, $command->uuid);
        $this->assertSame('john@example.com', $command->email);
        $this->assertSame('new_password', $command->password);
        $this->assertSame(['ROLE_ADMIN'], $command->roles);
    }

    #[Test]
    public function updateUserCommandPasswordIsNullable(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = UpdateUserCommand::withData(
            uuid: $uuid,
            email: 'john@example.com',
        );

        $this->assertNull($command->password);
        $this->assertSame([], $command->roles);
    }
}
