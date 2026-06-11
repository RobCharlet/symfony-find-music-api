<?php

namespace App\User\Domain;

use App\User\Domain\Exception\CannotFollowSelfException;
use Symfony\Component\Uid\Uuid;

final readonly class Follow
{
    private function __construct(
        private Uuid $followerUuid,
        private Uuid $followedUuid,
        private \DateTimeImmutable $followedAt,
    ) {
    }

    /**
     * @throws CannotFollowSelfException when a user tries to follow themselves
     */
    public static function create(Uuid $followerUuid, Uuid $followedUuid): self
    {
        if ($followerUuid->equals($followedUuid)) {
            throw new CannotFollowSelfException();
        }

        return new self($followerUuid, $followedUuid, new \DateTimeImmutable());
    }

    public function getFollowerUuid(): Uuid
    {
        return $this->followerUuid;
    }

    public function getFollowedUuid(): Uuid
    {
        return $this->followedUuid;
    }

    public function getFollowedAt(): \DateTimeImmutable
    {
        return $this->followedAt;
    }
}
