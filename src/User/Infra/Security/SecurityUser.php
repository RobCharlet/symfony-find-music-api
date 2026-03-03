<?php

namespace App\User\Infra\Security;

use App\User\Domain\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private Uuid $uuid,
        private string $email,
        private string $password,
        private array $roles = [],
    ) {
    }

    public static function fromDomain(User $user): self
    {
        return new self(
            $user->getUuid(),
            $user->getEmail(),
            $user->getPassword(),
            $user->getRoles(),
        );
    }

    public function updateFromDomain(User $user): void
    {
        $this->email = $user->getEmail();
        $this->password = $user->getPassword();
        $this->roles = $user->getRoles();
    }

    public function toDomain(): User
    {
        return new User(
            $this->uuid,
            $this->email,
            $this->password,
            $this->roles,
        );
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
