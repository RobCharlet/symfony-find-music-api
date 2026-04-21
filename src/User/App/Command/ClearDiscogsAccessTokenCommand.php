<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class ClearDiscogsAccessTokenCommand
{
    public function __construct(
        public Uuid $userUuid,
    ) {
    }

    public static function withUserUuid(Uuid $userUuid): self
    {
        return new self($userUuid);
    }
}
