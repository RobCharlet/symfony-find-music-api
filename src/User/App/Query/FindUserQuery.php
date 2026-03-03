<?php

namespace App\User\App\Query;

use Symfony\Component\Uid\Uuid;

readonly class FindUserQuery
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }

    public static function withUuid(Uuid $uuid): self
    {
        return new self($uuid);
    }
}
