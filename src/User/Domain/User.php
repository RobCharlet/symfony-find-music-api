<?php

namespace App\User\Domain;

use Symfony\Component\Uid\Uuid;

final class User
{
    public function __construct(
        private readonly Uuid $uuid,
        private string $email,
        private string $password,
        private array $roles = [],
        private bool $isPublic = false,
        private ?string $shareToken = null,
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

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    /**
     * Generates a share token if the user does not have one yet (idempotent).
     */
    public function ensureShareToken(): string
    {
        if (null === $this->shareToken) {
            $this->shareToken = bin2hex(random_bytes(16));
        }

        return $this->shareToken;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function update(
        string $email,
        array $roles,
        bool $isPublic,
        ?string $password = null,
    ): void {
        $this->email = $email;
        $this->roles = $roles;
        $this->isPublic = $isPublic;
        if (null !== $password) {
            $this->password = $password;
        }
    }
}
