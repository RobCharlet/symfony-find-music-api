<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\FollowUserCommand;
use App\User\App\CommandHandler\FollowUserCommandHandler;
use App\User\Domain\Exception\CannotFollowSelfException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Follow;
use App\User\Domain\Repository\FollowReaderInterface;
use App\User\Domain\Repository\FollowWriterInterface;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FollowUserCommandHandlerTest extends TestCase
{
    #[Test]
    public function followsPublicUser(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');
        $trane = new User($traneUuid, 'trane@example.com', 'hashed', ['ROLE_USER'], true);

        $mockUserReader = $this->createMock(UserReaderInterface::class);
        $mockUserReader->expects($this->once())->method('findUserByUuid')->with($traneUuid)->willReturn($trane);

        $mockFollowReader = $this->createMock(FollowReaderInterface::class);
        $mockFollowReader->expects($this->once())->method('isFollowing')->with($milesUuid, $traneUuid)->willReturn(false);

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (Follow $follow): bool => $follow->getFollowerUuid()->equals($milesUuid)
                    && $follow->getFollowedUuid()->equals($traneUuid)
            ));

        $handler = new FollowUserCommandHandler($mockUserReader, $mockFollowReader, $mockFollowWriter);
        $handler(FollowUserCommand::forUsers($milesUuid, $traneUuid));
    }

    #[Test]
    public function rejectsSelfFollow(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $mockUserReader = $this->createMock(UserReaderInterface::class);
        $mockUserReader->expects($this->never())->method('findUserByUuid');

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->never())->method('save');

        $this->expectException(CannotFollowSelfException::class);

        $handler = new FollowUserCommandHandler(
            $mockUserReader,
            $this->createStub(FollowReaderInterface::class),
            $mockFollowWriter,
        );
        $handler(FollowUserCommand::forUsers($milesUuid, $milesUuid));
    }

    #[Test]
    public function privateTargetIsIndistinguishableFromMissing(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');
        $trane = new User($traneUuid, 'trane@example.com', 'hashed', ['ROLE_USER'], false);

        $mockUserReader = $this->createMock(UserReaderInterface::class);
        $mockUserReader->expects($this->once())->method('findUserByUuid')->with($traneUuid)->willReturn($trane);

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->never())->method('save');

        $this->expectException(UserNotFoundException::class);

        $handler = new FollowUserCommandHandler(
            $mockUserReader,
            $this->createStub(FollowReaderInterface::class),
            $mockFollowWriter,
        );
        $handler(FollowUserCommand::forUsers($milesUuid, $traneUuid));
    }

    #[Test]
    public function missingTargetPropagates(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');

        $mockUserReader = $this->createMock(UserReaderInterface::class);
        $mockUserReader->expects($this->once())->method('findUserByUuid')->willThrowException(new UserNotFoundException());

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->never())->method('save');

        $this->expectException(UserNotFoundException::class);

        $handler = new FollowUserCommandHandler(
            $mockUserReader,
            $this->createStub(FollowReaderInterface::class),
            $mockFollowWriter,
        );
        $handler(FollowUserCommand::forUsers($milesUuid, $traneUuid));
    }

    #[Test]
    public function followingAnAlreadyFollowedUserIsANoOp(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');
        $trane = new User($traneUuid, 'trane@example.com', 'hashed', ['ROLE_USER'], true);

        $mockUserReader = $this->createMock(UserReaderInterface::class);
        $mockUserReader->expects($this->once())->method('findUserByUuid')->with($traneUuid)->willReturn($trane);

        $mockFollowReader = $this->createMock(FollowReaderInterface::class);
        $mockFollowReader->expects($this->once())->method('isFollowing')->with($milesUuid, $traneUuid)->willReturn(true);

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->never())->method('save');

        $handler = new FollowUserCommandHandler($mockUserReader, $mockFollowReader, $mockFollowWriter);
        $handler(FollowUserCommand::forUsers($milesUuid, $traneUuid));
    }
}
