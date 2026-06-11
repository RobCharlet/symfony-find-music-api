<?php

namespace App\User\Domain\Repository;

use App\Collection\Domain\PaginatorInterface;
use Symfony\Component\Uid\Uuid;

interface FollowReaderInterface
{
    public function isFollowing(Uuid $followerUuid, Uuid $followedUuid): bool;

    /**
     * Paginated list of users the given user follows.
     */
    public function findFollowing(Uuid $followerUuid, int $page, int $limit): PaginatorInterface;

    /**
     * Paginated list of users following the given user.
     */
    public function findFollowers(Uuid $followedUuid, int $page, int $limit): PaginatorInterface;
}
