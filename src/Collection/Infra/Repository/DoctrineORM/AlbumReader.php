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
use Doctrine\ORM\QueryBuilder;
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

    public function findAllByOwnerUuidWithPagination(
        Uuid $ownerUuid,
        int $page,
        int $limit,
        ?string $sortBy,
        ?string $sortOrder,
        ?bool $isFavorite,
        ?string $genre,
    ): PaginatorInterface {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('a')
            ->addSelect('e')
            ->from(Album::class, 'a')
            ->leftJoin('a.externalReferences', 'e')
            ->where('a.ownerUuid = :ownerUuid')
            ->setParameter('ownerUuid', $ownerUuid);

        if (null !== $isFavorite) {
            $qb->andWhere('a.isFavorite = :isFavorite')
                ->setParameter('isFavorite', $isFavorite);
        }

        if (null !== $genre) {
            $qb->andWhere('a.genre = :genre')
                ->setParameter('genre', $genre);
        }

        $sortBy = null !== $sortBy ? trim($sortBy) : null;
        $sortOrder = null !== $sortOrder ? trim($sortOrder) : 'ASC';

        if ($sortBy && !array_key_exists($sortBy, self::SORT_COLUMN_MAP)) {
            throw new \InvalidArgumentException('Invalid sortBy: '.$sortBy);
        }

        if ($sortBy && $sortOrder) {
            $qb->orderBy(self::SORT_COLUMN_MAP[$sortBy], SortDirectionEnum::from($sortOrder)->value);
        } else {
            $qb->orderBy('a.uuid', $sortOrder);
        }

        $paginator = new Paginator(new QueryAdapter($qb->getQuery()));
        $paginator->setAllowOutOfRangePages(true);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findStatsByOwnerUuid(Uuid $ownerUuid): array
    {
        $stats = [];

        $totalAlbumQuery = $this->createCollectionOwnerQueryBuilder($ownerUuid)
            ->select('count(a.uuid) as totalAlbums')
            ->getQuery();

        $stats['totalAlbums'] = (int) $totalAlbumQuery->getResult()[0]['totalAlbums'];

        $genresQuery = $this->createCollectionOwnerQueryBuilder($ownerUuid)
            ->select('distinct a.genre', 'count(a.uuid) as count')
            ->groupBy('a.genre')
            ->getQuery();

        $stats['genres'] = array_column(
            $genresQuery->getResult(),
            'count',
            'genre'
        );

        $this->replaceEmptyKeyWithUnknown($stats['genres']);

        $formatsQuery = $this->createCollectionOwnerQueryBuilder($ownerUuid)
            ->select('a.format')
            ->getQuery();

        $formatRows = $formatsQuery->getResult();

        $explodedFormats = [];

        foreach ($formatRows as $formatRow) {
            $explodedFormats[] = array_map('trim', explode(',', $formatRow['format']));
        }

        $allFormats = array_merge(...$explodedFormats);

        $formatCounts = array_count_values($allFormats);
        ksort($formatCounts);

        $stats['formats'] = $formatCounts;

        $releaseYearQuery = $this->createCollectionOwnerQueryBuilder($ownerUuid)
            ->select('distinct a.releaseYear', 'count(a.uuid) as count')
            ->groupBy('a.releaseYear')
            ->getQuery();

        $stats['releaseYears'] = array_column(
            $releaseYearQuery->getResult(),
            'count',
            'releaseYear'
        );

        $this->replaceEmptyKeyWithUnknown($stats['releaseYears']);

        $labelQuery = $this->createCollectionOwnerQueryBuilder($ownerUuid)
            ->select('distinct a.label', 'count(a.uuid) as count')
            ->groupBy('a.label')
            ->orderBy('a.label', 'ASC')
            ->getQuery();

        $stats['labels'] = array_column(
            $labelQuery->getResult(),
            'count',
            'label'
        );

        $this->replaceEmptyKeyWithUnknown($stats['labels']);

        return $stats;
    }

    private function createCollectionOwnerQueryBuilder(Uuid $ownerUuid): QueryBuilder
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->from(Album::class, 'a')
            ->where('a.ownerUuid = :ownerUuid')
            ->setParameter('ownerUuid', $ownerUuid);
    }

    private function replaceEmptyKeyWithUnknown(array &$statGroup): void
    {
        if (isset($statGroup[''])) {
            $statGroup['Unknown'] = $statGroup[''];
            unset($statGroup['']);
        }
    }

    public function findAllByOwnerUuid(Uuid $ownerUuid): iterable
    {
        $conn = $this->entityManager->getConnection();

        $result = $conn->executeQuery(
            'SELECT
            a.uuid as album_uuid, a.title, a.artist, a.release_year, a.format, a.genre, a.label, a.cover_url,
            a.created_at, a.updated_at, a.is_favorite, e.uuid as external_reference_uuid, e.platform, e.metadata
            FROM album a
            LEFT JOIN external_reference e ON a.uuid = e.album_uuid
            WHERE a.owner_uuid = :ownerUuid
            /* Orders by album UUID to group external references per album */
            ORDER BY a.uuid;
            ',
            ['ownerUuid' => $ownerUuid]
        );

        $currentCollection = null;

        foreach ($result->iterateAssociative() as $row) {
            // Current album is complete — yield it and move to the next one.
            if (null !== $currentCollection && $currentCollection['albumUuid'] !== $row['album_uuid']) {
                yield $currentCollection;
                // Reset for the next album.
                $currentCollection = null;
            }

            // Start a new album entry.
            if (null === $currentCollection) {
                $currentCollection = [
                    'albumUuid' => $row['album_uuid'],
                    'title' => $row['title'],
                    'artist' => $row['artist'],
                    'releaseYear' => $row['release_year'],
                    'format' => $row['format'],
                    'genre' => $row['genre'],
                    'label' => $row['label'],
                    'coverUrl' => $row['cover_url'],
                    'createdAt' => $row['created_at'],
                    'updatedAt' => $row['updated_at'],
                    'isFavorite' => $row['is_favorite'],
                ];

                $currentCollection['externalReferences'] = [];
            }

            // Append external reference to the current album.
            if (null !== $row['external_reference_uuid']) {
                $currentCollection['externalReferences'][] = [
                    'referenceUuid' => $row['external_reference_uuid'],
                    'platform' => $row['platform'],
                    'metadata' => $row['metadata'],
                ];
            }
        }

        // Yield the last album (no next row to trigger the yield above).
        if (null !== $currentCollection) {
            yield $currentCollection;
        }
    }
}
