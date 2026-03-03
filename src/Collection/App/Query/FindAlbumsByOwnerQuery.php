<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindAlbumsByOwnerQuery
{
    public function __construct(
        public Uuid $ownerUuid,
    ) {
    }

    public static function withOwnerUuid(Uuid $ownerUuid): self
    {
        return new self($ownerUuid);
    }
}
