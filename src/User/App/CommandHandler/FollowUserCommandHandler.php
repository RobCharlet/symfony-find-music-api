<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\FollowUserCommand;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Follow;
use App\User\Domain\Repository\FollowReaderInterface;
use App\User\Domain\Repository\FollowWriterInterface;
use App\User\Domain\Repository\UserReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FollowUserCommandHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
        private FollowReaderInterface $followReader,
        private FollowWriterInterface $followWriter,
    ) {
    }

    /**
     * Idempotent: following an already-followed user is a no-op.
     */
    public function __invoke(FollowUserCommand $command): void
    {
        $follow = Follow::create($command->followerUuid, $command->followedUuid);

        $user = $this->userReader->findUserByUuid($command->followedUuid);

        // Non-public profiles are indistinguishable from missing ones to avoid leaking existence.
        if (!$user->isPublic()) {
            throw new UserNotFoundException();
        }

        if ($this->followReader->isFollowing($command->followerUuid, $command->followedUuid)) {
            return;
        }

        $this->followWriter->save($follow);
    }
}
