<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class UnfollowUserCommand
{
    public function __construct(
        public Uuid $followerUuid,
        public Uuid $followedUuid,
    ) {
    }

    public static function forUsers(Uuid $followerUuid, Uuid $followedUuid): self
    {
        return new self($followerUuid, $followedUuid);
    }
}
