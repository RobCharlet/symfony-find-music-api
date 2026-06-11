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
        $this->entityManager->persist(Follow::fromDomain($follow));
        $this->entityManager->flush();
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
