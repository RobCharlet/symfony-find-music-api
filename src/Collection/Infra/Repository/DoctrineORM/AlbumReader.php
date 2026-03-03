<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Infra\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Uid\Uuid;

readonly class AlbumReader implements AlbumReaderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(int $page, int $limit): PaginatorInterface
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('a')
            ->addSelect('e')
            ->from(Album::class, 'a')
            ->leftJoin('a.externalReferences', 'e')
            ->orderBy('a.title', 'DESC')
            ->getQuery();

        $paginator = new Paginator(new QueryAdapter($query));
        $paginator->setAllowOutOfRangePages(true);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findByUuid(Uuid $uuid): Album
    {
        $album = $this->entityManager->getRepository(Album::class)->findOneBy(['uuid' => $uuid]);
        if (null === $album) {
            throw new AlbumNotFoundException();
        }

        return $album;
    }

    public function findAllByOwnerUuid(Uuid $ownerUuid): array
    {
        return $this->entityManager->getRepository(Album::class)->findBy(['ownerUuid' => $ownerUuid]);
    }
}
