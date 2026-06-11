<?php

namespace App\User\Infra\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'app_follow')]
#[ORM\Index(name: 'idx_follow_followed_uuid', columns: ['followed_uuid'])]
class Follow
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'follower_uuid', type: 'uuid')]
        private Uuid $followerUuid,
        #[ORM\Id]
        #[ORM\Column(name: 'followed_uuid', type: 'uuid')]
        private Uuid $followedUuid,
        #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function getFollowerUuid(): Uuid
    {
        return $this->followerUuid;
    }

    public function getFollowedUuid(): Uuid
    {
        return $this->followedUuid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
