<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

class GetStatsByOwnerQuery
{
    public function __construct(
        public Uuid $ownerUuid,
        public Uuid $userUuid,
        public bool $isAdmin,
    ) {

    }

    public static function withOwnerUuid(
        Uuid $ownerUuid,
        Uuid $userUuid,
        bool $isAdmin,
    ): GetStatsByOwnerQuery {
        return new self(
            $ownerUuid,
            $userUuid,
            $isAdmin
        );
    }
}
