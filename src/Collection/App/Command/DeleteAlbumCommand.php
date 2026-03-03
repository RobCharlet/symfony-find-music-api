<?php

namespace App\Collection\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteAlbumCommand
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $ownerUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withUuid(
        Uuid $uuid,
        Uuid $ownerUuid,
        bool $isAdmin,
    ): self {
        return new self(
            uuid: $uuid,
            ownerUuid: $ownerUuid,
            isAdmin: $isAdmin
        );
    }
}
