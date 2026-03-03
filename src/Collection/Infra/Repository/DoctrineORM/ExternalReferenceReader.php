<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Infra\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Uid\Uuid;

readonly class ExternalReferenceReader implements ExternalReferenceReaderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findAll(int $page, int $limit): PaginatorInterface
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(ExternalReference::class, 'e')
            ->orderBy('e.album', 'ASC')
            ->getQuery();

        $paginator =  new Paginator(new QueryAdapter($query));
        $paginator->setAllowOutOfRangePages(true);
        $paginator->setMaxPerPage($limit);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    public function findByUuid(Uuid $uuid): ExternalReference
    {
        // Preload album to avoid lazy-loading hydration on readonly Album properties.
        // Which causes Exception for Attempting to change readonly property
        // when $externalReference->getAlbum()->getOwnerUuid()
        $externalReference = $this->entityManager
            ->createQueryBuilder()
            ->select('externalReference', 'album')
            ->from(ExternalReference::class, 'externalReference')
            ->join('externalReference.album', 'album')
            ->where('externalReference.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $externalReference) {
            throw new ExternalReferenceNotFoundException();
        }

        return $externalReference;
    }

    public function findAllByAlbumUuid(Uuid $albumUuid): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('externalReference', 'album')
            ->from(ExternalReference::class, 'externalReference')
            ->join('externalReference.album', 'album')
            ->where('album.uuid = :albumUuid')
            ->setParameter('albumUuid', $albumUuid)
            ->getQuery()
            ->getResult();
    }

    public function existsByOwnerPlatformExternalId(Uuid $ownerUuid, string $platform, string $externalId): bool
    {
        $result = $this->entityManager
            ->createQueryBuilder()
            ->select('externalReference', 'album')
            ->from(ExternalReference::class, 'externalReference')
            ->leftJoin('externalReference.album', 'album')
            ->where('album.ownerUuid = :ownerUuid')
            ->andWhere('externalReference.platform = :platform')
            ->andWhere('externalReference.externalId = :externalId')
            ->setParameter('ownerUuid', $ownerUuid)
            ->setParameter('platform', $platform)
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult();

        return null !== $result;
    }
}
