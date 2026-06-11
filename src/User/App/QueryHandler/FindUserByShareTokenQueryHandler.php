<?php

namespace App\User\App\QueryHandler;

use App\User\App\Query\FindUserByShareTokenQuery;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindUserByShareTokenQueryHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
    ) {
    }

    public function __invoke(FindUserByShareTokenQuery $query): User
    {
        // The share token itself is the access grant: no isPublic check.
        return $this->userReader->findUserByShareToken($query->shareToken);
    }
}
