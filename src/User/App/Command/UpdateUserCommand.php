<?php

namespace App\User\App\Command;

use Symfony\Component\Uid\Uuid;

readonly class UpdateUserCommand
{
    public function __construct(
        public Uuid $uuid,
        public Uuid $requesterUuid,
        public ?string $email,
        public ?string $password,
        public ?string $currentPassword,
        public mixed $roles,
        public bool $isAdmin,
    ) {
    }

    public static function withData(
        Uuid $uuid,
        Uuid $requesterUuid,
        ?string $email,
        ?string $password = null,
        ?string $currentPassword = null,
        mixed $roles = [],
        bool $isAdmin = false,
    ): self {
        return new self(
            uuid: $uuid,
            requesterUuid: $requesterUuid,
            email: $email,
            password: $password,
            currentPassword: $currentPassword,
            roles: $roles,
            isAdmin: $isAdmin
        );
    }
}
