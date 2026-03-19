<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\SortByEnum;
use App\Collection\Domain\SortDirectionEnum;
use App\Collection\Infra\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Uid\Uuid;

readonly class AlbumReader implements AlbumReaderInterface
{
    private const array SORT_COLUMN_MAP = [
        SortByEnum::Title->value => 'a.title',
        SortByEnum::Artist->value => 'a.artist',
        SortByEnum::Genre->value => 'a.genre',
        SortByEnum::ReleaseYear->value => 'a.releaseYear',
        SortByEnum::Format->value => 'a.format',
        SortByEnum::Label->value => 'a.label',
    ];

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

    public function findAllByOwnerUuid(
        Uuid $ownerUuid,
        int $page,
        int $limit,
        ?string $sortBy,
        ?string $sortOrder,
        ?bool $isFavorite,
        ?string $genre,
    ): PaginatorInterface {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('a')
            ->addSelect('e')
            ->from(Album::class, 'a')
            ->leftJoin('a.externalReferences', 'e')
            ->where('a.ownerUuid = :ownerUuid')
            ->setParameter('ownerUuid', $ownerUuid);

        if (null !== $isFavorite) {
            $query->andWhere('a.isFavorite = :isFavorite')
                ->setParameter('isFavorite', $isFavorite);
        }

        if (null !== $genre) {
            $query->andWhere('a.genre = :genre')
                ->setParameter('genre', $genre);
        }

        $sortBy = null !== $sortBy ? trim($sortBy) : null;
        $sortOrder = null !== $sortOrder ? trim($sortOrder) : 'ASC';

        if ($sortBy && !array_key_exists($sortBy, self::SORT_COLUMN_MAP)) {
            throw new \InvalidArgumentException('Invalid sortBy: '.$sortBy);
        }

        if ($sortBy && $sortOrder) {
            $query->orderBy(self::SORT_COLUMN_MAP[$sortBy], SortDirectionEnum::from($sortOrder)->value);
        } else {
            $query->orderBy('a.uuid', $sortOrder);
        }

        $paginator = new Paginator(new QueryAdapter($query->getQuery()));
        $paginator->setAllowOutOfRangePages(true);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
