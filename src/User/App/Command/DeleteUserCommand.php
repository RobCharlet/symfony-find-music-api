<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteUserCommand
{
    public function __construct(
        public Uuid $uuid,
    ) {
    }

    public static function withUuid(
        Uuid $uuid,
    ): self {
        return new self(
            uuid: $uuid,
        );
    }
}
