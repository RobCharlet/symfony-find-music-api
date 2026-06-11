<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\User\Domain\Follow as DomainFollow;
use App\User\Domain\Repository\FollowWriterInterface;
use App\User\Infra\Entity\Follow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

readonly class FollowWriter implements FollowWriterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(DomainFollow $follow): void
    {
        // Idempotent insert: concurrent follows must not surface a PK conflict.
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO app_follow (follower_uuid, followed_uuid, created_at)
             VALUES (:follower_uuid, :followed_uuid, :created_at)
             ON CONFLICT DO NOTHING',
            [
                'follower_uuid' => $follow->getFollowerUuid()->toRfc4122(),
                'followed_uuid' => $follow->getFollowedUuid()->toRfc4122(),
                'created_at' => $follow->getFollowedAt()->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function delete(Uuid $followerUuid, Uuid $followedUuid): void
    {
        $follow = $this->entityManager->getRepository(Follow::class)->findOneBy([
            'followerUuid' => $followerUuid,
            'followedUuid' => $followedUuid,
        ]);

        if (null === $follow) {
            return;
        }

        $this->entityManager->remove($follow);
        $this->entityManager->flush();
    }
}
