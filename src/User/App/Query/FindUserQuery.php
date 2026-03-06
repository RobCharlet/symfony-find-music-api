<?php

namespace App\User\App\Query;

use Symfony\Component\Uid\Uuid;

readonly class FindUserQuery
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $requesterUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withUuid(Uuid $uuid, Uuid $requesterUuid, bool $isAdmin): self
    {
        return new self($uuid, $requesterUuid, $isAdmin);
    }
}
