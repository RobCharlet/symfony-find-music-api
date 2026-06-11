<?php

namespace App\User\App\QueryHandler;

use App\Collection\Domain\PaginatorInterface;
use App\User\App\Query\FindFollowersQuery;
use App\User\Domain\Repository\FollowReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindFollowersQueryHandler
{
    public function __construct(
        private FollowReaderInterface $followReader,
    ) {
    }

    public function __invoke(FindFollowersQuery $query): PaginatorInterface
    {
        return $this->followReader->findFollowers($query->userUuid, $query->page, $query->limit);
    }
}
