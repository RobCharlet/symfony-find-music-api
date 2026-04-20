<?php

namespace App\User\Infra\Security;

use App\Shared\Domain\UuidAwareUserInterface;
use App\User\Domain\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, UuidAwareUserInterface
{
    public function __construct(
        private Uuid $uuid,
        private string $email,
        private string $password,
        private array $roles = [],
        private ?string $discogsAccessToken = null,
        private ?string $discogsAccessTokenNonce = null,
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

    public function clearDiscogsAccessToken(): void
    {
        $this->discogsAccessToken = null;
        $this->discogsAccessTokenNonce = null;
    }

    public function getDiscogsAccessToken(): ?string
    {
        return $this->discogsAccessToken;
    }

    public function getDiscogsAccessTokenNonce(): ?string
    {
        return $this->discogsAccessTokenNonce;
    }

    public function setDiscogsAccessToken(?string $discogsAccessToken, ?string $discogsAccessTokenNonce): void
    {
        $this->discogsAccessToken = $discogsAccessToken;
        $this->discogsAccessTokenNonce = $discogsAccessTokenNonce;

    }
}
