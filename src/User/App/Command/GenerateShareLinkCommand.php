<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class GenerateShareLinkCommand
{
    public function __construct(
        public Uuid $userUuid,
    ) {
    }

    public static function forUser(Uuid $userUuid): self
    {
        return new self($userUuid);
    }
}
