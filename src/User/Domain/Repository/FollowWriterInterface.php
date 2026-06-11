<?php

namespace App\User\Domain\Repository;

use App\User\Domain\Follow;
use Symfony\Component\Uid\Uuid;

interface FollowWriterInterface
{
    public function save(Follow $follow): void;

    /**
     * Removes the follow relation if it exists (idempotent).
     */
    public function delete(Uuid $followerUuid, Uuid $followedUuid): void;
}
