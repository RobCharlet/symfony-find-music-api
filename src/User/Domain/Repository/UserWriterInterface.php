<?php

namespace App\User\Domain\Repository;

use App\User\Domain\User;
use Symfony\Component\Uid\Uuid;

interface UserWriterInterface
{
    public function delete(User $user): void;

    public function save(User $user): void;

    public function update(User $user): void;

    /**
     * Atomically assigns the share token if the user has none, and returns the
     * token actually stored (the winner under concurrent calls).
     */
    public function claimShareToken(Uuid $uuid, string $shareToken): string;
}
