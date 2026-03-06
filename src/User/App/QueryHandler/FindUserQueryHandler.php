<?php

namespace App\User\App\QueryHandler;

use App\User\App\Query\FindUserQuery;
use App\User\Domain\Exception\UserAccessForbiddenException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindUserQueryHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
    ) {
    }

    public function __invoke(FindUserQuery $query): User
    {
        if (!$query->isAdmin && !$query->requesterUuid->equals($query->uuid)) {
            throw new UserAccessForbiddenException();
        }

        return $this->userReader->findUserByUuid($query->uuid);
    }
}
