<?php

namespace App\User\Infra\Security;

use App\User\Domain\PasswordHasherInterface;
use App\User\Domain\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class PasswordHasherAdapter implements PasswordHasherInterface
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function hash(User $user, string $plainPassword): string
    {
        $securityUser = SecurityUser::fromDomain($user);

        return $this->userPasswordHasher->hashPassword($securityUser, $plainPassword);
    }
}
