<?php

namespace App\User\App\QueryHandler;

use App\User\App\Query\FindUserQuery;
use App\User\Domain\Repository\UserReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindUserQueryHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
    ) {
    }

    public function __invoke(FindUserQuery $query)
    {
        return $this->userReader->findUserByUuid($query->uuid);
    }
}
