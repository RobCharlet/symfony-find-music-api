<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class UpdateUserCommand
{
    public function __construct(
        public Uuid $uuid,
        public ?string $email,
        public ?string $password = null,
        public mixed $roles = [],
    ) {
    }

    public static function withData(
        Uuid $uuid,
        ?string $email,
        mixed $roles,
        ?string $password,
    ): self {
        return new self(
            uuid: $uuid,
            email: $email,
            password: $password,
            roles: $roles,
        );
    }
}
