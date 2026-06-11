<?php

namespace App\User\App\QueryHandler;

use App\Collection\Domain\PaginatorInterface;
use App\User\App\Query\FindFollowingQuery;
use App\User\Domain\Repository\FollowReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class FindFollowingQueryHandler
{
    public function __construct(
        private FollowReaderInterface $followReader,
    ) {
    }

    public function __invoke(FindFollowingQuery $query): PaginatorInterface
    {
        return $this->followReader->findFollowing($query->userUuid, $query->page, $query->limit);
    }
}
