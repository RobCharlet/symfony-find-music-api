<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\App\Command;

use App\User\App\Command\CreateUserCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class CreateUserCommandTest extends TestCase
{
    #[Test]
    public function createUserCommandForSelfRegistrationForcesRoleUser(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = CreateUserCommand::forSelfRegistration(
            $uuid,
            'john@example.com',
            'plain_password',
        );

        $this->assertSame($uuid, $command->uuid);
        $this->assertSame('john@example.com', $command->email);
        $this->assertSame('plain_password', $command->password);
        $this->assertSame(['ROLE_USER'], $command->roles);
    }

    #[Test]
    public function createUserCommandForAdminCreationUsesProvidedRoles(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = CreateUserCommand::forAdminCreation(
            $uuid,
            'admin-created@example.com',
            'plain_password',
            ['ROLE_ADMIN'],
        );

        $this->assertSame($uuid, $command->uuid);
        $this->assertSame('admin-created@example.com', $command->email);
        $this->assertSame('plain_password', $command->password);
        $this->assertSame(['ROLE_ADMIN'], $command->roles);
    }
}
