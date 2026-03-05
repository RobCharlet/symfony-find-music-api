<?php

namespace App\User\Domain;

use Symfony\Component\Uid\Uuid;

final class User
{
    public function __construct(
        private Uuid $uuid,
        private string $email,
        private string $password,
        private array $roles = [],
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function update(
        string $email,
        array $roles,
        ?string $password = null,
    ): void {
        $this->email = $email;
        $this->roles = $roles;
        if (null !== $password) {
            $this->password = $password;
        }
    }
}
