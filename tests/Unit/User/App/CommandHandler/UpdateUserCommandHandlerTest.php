<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\UpdateUserCommand;
use App\User\App\CommandHandler\UpdateUserCommandHandler;
use App\User\Domain\PasswordHasherInterface;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use App\User\Domain\UserNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UpdateUserCommandHandlerTest extends TestCase
{
    #[Test]
    public function updatesUserWithNewPassword(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $existingUser = new User($uuid, 'old@example.com', 'old_hashed', ['ROLE_USER']);

        $command = UpdateUserCommand::withData(
            $uuid,
            'new@example.com',
            ['ROLE_ADMIN'],
            'new_plain',
        );

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->with($uuid)
            ->willReturn($existingUser);

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher
            ->expects($this->once())
            ->method('hash')
            ->willReturn('new_hashed');

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (User $user) {
                $this->assertSame('new@example.com', $user->getEmail());
                $this->assertSame('new_hashed', $user->getPassword());
                $this->assertContains('ROLE_ADMIN', $user->getRoles());
            });

        $handler = new UpdateUserCommandHandler($mockHasher, $mockReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function updatesUserWithoutPasswordThrowsTypeError(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $existingUser = new User($uuid, 'old@example.com', 'old_hashed', ['ROLE_USER']);

        $command = new UpdateUserCommand(
            uuid: $uuid,
            email: 'new@example.com',
            roles: ['ROLE_ADMIN'],
        );

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->with($uuid)
            ->willReturn($existingUser);

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher
            ->expects($this->never())
            ->method('hash');

        $mockWriter = $this->createStub(UserWriterInterface::class);

        // Documents bug: User::update() without password assigns null to string property
        $this->expectException(\TypeError::class);

        $handler = new UpdateUserCommandHandler($mockHasher, $mockReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function throwsUserNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = new UpdateUserCommand(
            uuid: $uuid,
            email: 'new@example.com',
        );

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->with($uuid)
            ->willThrowException(new UserNotFoundException());

        $mockHasher = $this->createMock(PasswordHasherInterface::class);
        $mockHasher
            ->expects($this->never())
            ->method('hash');

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter
            ->expects($this->never())
            ->method('update');

        $this->expectException(UserNotFoundException::class);

        $handler = new UpdateUserCommandHandler($mockHasher, $mockReader, $mockWriter);
        $handler($command);
    }
}
