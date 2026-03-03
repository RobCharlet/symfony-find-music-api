<?php

namespace App\Shared\UI;

use Symfony\Component\Uid\Uuid;

final readonly class UserAuthorization
{
    public function __construct(
        public Uuid $userUuid,
        public bool $isAdmin,
    ) {
    }
}
