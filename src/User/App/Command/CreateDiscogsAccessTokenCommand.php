<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CreateDiscogsAccessTokenCommand
{
    public function __construct(
        public Uuid $userUuid,
        public string $discogsAccessToken,
        public bool $isAdmin,
    ) {
    }

    public static function withAccessToken(Uuid $userUuid, string $discogsAccessToken, bool $isAdmin): self
    {
        return new self($userUuid, $discogsAccessToken, $isAdmin);
    }
}
