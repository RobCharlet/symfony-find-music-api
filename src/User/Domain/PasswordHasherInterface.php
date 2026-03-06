<?php

namespace App\User\Domain;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;

    public function verify(User $user, string $plainPassword): bool;
}
