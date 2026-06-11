<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindPublicCollectionByOwnerQuery;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindPublicCollectionByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindPublicCollectionByOwnerQuery $query): PaginatorInterface
    {
        return $this->albumReader->findAllByOwnerUuidWithPagination(
            $query->ownerUuid,
            $query->page,
            $query->limit,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );
    }
}
