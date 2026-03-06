<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteUserCommand
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $requesterUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withUuid(
        Uuid $uuid,
        Uuid $requesterUuid,
        bool $isAdmin,
    ): self {
        return new self(
            uuid: $uuid,
            requesterUuid: $requesterUuid,
            isAdmin: $isAdmin,
        );
    }
}
