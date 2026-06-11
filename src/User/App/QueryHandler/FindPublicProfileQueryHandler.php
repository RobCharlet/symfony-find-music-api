<?php

namespace App\User\App\QueryHandler;

use App\User\App\Query\FindPublicProfileQuery;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindPublicProfileQueryHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
    ) {
    }

    public function __invoke(FindPublicProfileQuery $query): User
    {
        $user = $this->userReader->findUserByUuid($query->uuid);

        // Non-public profiles are indistinguishable from missing ones to avoid leaking existence.
        if (!$user->isPublic()) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
