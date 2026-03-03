<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\CreateUserCommand;
use App\User\App\CommandHandler\CreateUserCommandHandler;
use App\User\Domain\PasswordHasherInterface;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class CreateUserCommandHandlerTest extends TestCase
{
    #[Test]
    public function createUserCommandHandlerSavesUserWithHashedPassword(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = CreateUserCommand::forAdminCreation(
            $uuid,
            'john@example.com',
            'plain_password',
            ['ROLE_USER'],
        );

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher
            ->expects($this->once())
            ->method('hash')
            ->willReturn('hashed_xxx');

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (User $user) use ($uuid) {
                $this->assertSame($uuid, $user->getUuid());
                $this->assertSame('john@example.com', $user->getEmail());
                $this->assertSame('hashed_xxx', $user->getPassword());
                $this->assertContains('ROLE_USER', $user->getRoles());
            });

        $handler = new CreateUserCommandHandler($mockHasher, $mockWriter);
        $handler($command);
    }
}
