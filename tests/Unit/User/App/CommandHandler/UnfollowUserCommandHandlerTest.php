<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\UnfollowUserCommand;
use App\User\App\CommandHandler\UnfollowUserCommandHandler;
use App\User\Domain\Repository\FollowWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class UnfollowUserCommandHandlerTest extends TestCase
{
    #[Test]
    public function delegatesIdempotentDeleteToWriter(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');

        $mockFollowWriter = $this->createMock(FollowWriterInterface::class);
        $mockFollowWriter->expects($this->once())->method('delete')->with($milesUuid, $traneUuid);

        $handler = new UnfollowUserCommandHandler($mockFollowWriter);
        $handler(UnfollowUserCommand::forUsers($milesUuid, $traneUuid));
    }
}
