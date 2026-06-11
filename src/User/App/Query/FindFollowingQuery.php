<?php

namespace App\User\App\Query;

use Symfony\Component\Uid\Uuid;

readonly class FindFollowingQuery
{
    public function __construct(
        public Uuid $userUuid,
        public int $page,
        public int $limit,
    ) {
    }

    public static function forUser(Uuid $userUuid, int $page = 1, int $limit = 50): self
    {
        return new self($userUuid, $page, $limit);
    }
}
