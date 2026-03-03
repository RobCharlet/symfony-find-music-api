<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class CreateUserCommand
{
    public function __construct(
        public Uuid $uuid,
        public string $email,
        public string $password,
        public mixed $roles = ['ROLE_USER'],
    ) {
    }

    public static function forSelfRegistration(
        Uuid $uuid,
        string $email,
        string $password,
    ): self {
        return new self(
            uuid: $uuid,
            email: $email,
            password: $password,
            roles: ['ROLE_USER'],
        );
    }

    public static function forAdminCreation(
        Uuid $uuid,
        string $email,
        string $password,
        mixed $roles = ['ROLE_USER'],
    ): self {
        return new self(
            uuid: $uuid,
            email: $email,
            password: $password,
            roles: $roles,
        );
    }
}
