<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\DeleteUserCommand;
use App\User\App\CommandHandler\DeleteUserCommandHandler;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use App\User\Domain\UserNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class DeleteUserCommandHandlerTest extends TestCase
{
    #[Test]
    public function deletesUser(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $existingUser = new User($uuid, 'john@example.com', 'hashed', ['ROLE_USER']);

        $command = DeleteUserCommand::withUuid($uuid);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->with($uuid)
            ->willReturn($existingUser);

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter
            ->expects($this->once())
            ->method('delete')
            ->with($existingUser);

        $handler = new DeleteUserCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }

    #[Test]
    public function throwsUserNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $command = DeleteUserCommand::withUuid($uuid);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->with($uuid)
            ->willThrowException(new UserNotFoundException());

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UserNotFoundException::class);

        $handler = new DeleteUserCommandHandler($mockReader, $mockWriter);
        $handler($command);
    }
}
