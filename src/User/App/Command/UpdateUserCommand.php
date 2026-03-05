<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class UpdateUserCommand
{
    public function __construct(
        public Uuid $uuid,
        public ?string $email,
        public ?string $password,
        public mixed $roles,
    ) {
    }

    public static function withData(
        Uuid $uuid,
        ?string $email,
        ?string $password = null,
        mixed $roles = [],
    ): self {
        return new self(
            uuid: $uuid,
            email: $email,
            password: $password,
            roles: $roles,
        );
    }
}
