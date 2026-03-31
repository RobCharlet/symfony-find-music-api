<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindCollectionByOwnerQuery
{
    public function __construct(
        public Uuid $ownerUuid,
        public Uuid $requesterUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withOwnerUuid(
        Uuid $ownerUuid,
        Uuid $requesterUuid,
        bool $isAdmin,
    ): self {
        return new self(
            $ownerUuid,
            $requesterUuid,
            $isAdmin,
        );
    }
}
