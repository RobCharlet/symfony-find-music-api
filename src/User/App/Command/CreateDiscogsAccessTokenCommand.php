<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

final readonly class CreateDiscogsAccessTokenCommand
{
    public function __construct(
        public Uuid $userUuid,
        #[\SensitiveParameter]
        public string $discogsAccessToken,
    ) {
    }

    public static function withAccessToken(
        Uuid $userUuid,
        #[\SensitiveParameter]
        string $discogsAccessToken,
    ): self {
        return new self($userUuid, $discogsAccessToken);
    }
}
