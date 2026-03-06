<?php

namespace App\Tests\Unit\User\App\Command;

use App\User\App\Command\DeleteUserCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class DeleteUserCommandTest extends TestCase
{
    #[Test]
    public function deleteUserCommandIsCreatedFromUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = DeleteUserCommand::withUuid($uuid, $uuid, false);

        $this->assertSame($uuid, $command->uuid);
    }
}
