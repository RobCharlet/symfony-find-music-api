<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindExternalReferenceQuery
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $ownerUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withUuid(Uuid $uuid, Uuid $ownerUuid, bool $isAdmin): self
    {
        return new self($uuid, $ownerUuid, $isAdmin);
    }
}
