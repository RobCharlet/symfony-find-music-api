<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\Collection\Domain\PaginatorInterface;
use App\Collection\Infra\Paginator;
use App\User\Domain\Repository\FollowReaderInterface;
use App\User\Infra\Entity\Follow;
use App\User\Infra\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Uid\Uuid;

readonly class FollowReader implements FollowReaderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function isFollowing(Uuid $followerUuid, Uuid $followedUuid): bool
    {
        $follow = $this->entityManager->getRepository(Follow::class)->findOneBy([
            'followerUuid' => $followerUuid,
            'followedUuid' => $followedUuid,
        ]);

        return null !== $follow;
    }

    public function findFollowing(Uuid $followerUuid, int $page, int $limit): PaginatorInterface
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('u.uuid AS uuid', 'u.isPublic AS isPublic', 'f.createdAt AS followedAt')
            ->from(Follow::class, 'f')
            ->join(SecurityUser::class, 'u', Join::WITH, 'u.uuid = f.followedUuid')
            ->where('f.followerUuid = :uuid')
            ->setParameter('uuid', $followerUuid)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('u.uuid', 'ASC')
            ->getQuery();

        return $this->paginate($query, $page, $limit);
    }

    public function findFollowers(Uuid $followedUuid, int $page, int $limit): PaginatorInterface
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('u.uuid AS uuid', 'u.isPublic AS isPublic', 'f.createdAt AS followedAt')
            ->from(Follow::class, 'f')
            ->join(SecurityUser::class, 'u', Join::WITH, 'u.uuid = f.followerUuid')
            ->where('f.followedUuid = :uuid')
            ->setParameter('uuid', $followedUuid)
            ->orderBy('f.createdAt', 'DESC')
            ->addOrderBy('u.uuid', 'ASC')
            ->getQuery();

        return $this->paginate($query, $page, $limit);
    }

    private function paginate(Query $query, int $page, int $limit): PaginatorInterface
    {
        $paginator = new Paginator(new QueryAdapter($query));
        $paginator->setAllowOutOfRangePages(true);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
