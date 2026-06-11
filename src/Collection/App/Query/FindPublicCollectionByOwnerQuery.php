<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindPublicCollectionByOwnerQuery
{
    public function __construct(
        public Uuid $ownerUuid,
        public int $page,
        public int $limit,
    ) {
    }

    public static function withOwnerUuid(Uuid $ownerUuid, int $page = 1, int $limit = 50): self
    {
        return new self($ownerUuid, $page, $limit);
    }
}
