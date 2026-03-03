<?php

namespace App\User\Domain\Repository;

use App\User\Domain\User;

interface UserWriterInterface
{
    public function delete(User $user): void;

    public function save(User $user): void;

    public function update(User $user): void;
}
