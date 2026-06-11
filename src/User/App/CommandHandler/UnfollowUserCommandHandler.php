<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\UnfollowUserCommand;
use App\User\Domain\Repository\FollowWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UnfollowUserCommandHandler
{
    public function __construct(
        private FollowWriterInterface $followWriter,
    ) {
    }

    /**
     * Idempotent: unfollowing a user who is not followed is a no-op.
     */
    public function __invoke(UnfollowUserCommand $command): void
    {
        $this->followWriter->delete($command->followerUuid, $command->followedUuid);
    }
}
