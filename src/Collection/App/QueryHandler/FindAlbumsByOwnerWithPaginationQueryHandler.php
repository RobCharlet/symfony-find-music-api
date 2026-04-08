<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumsByOwnerWithPaginationQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindAlbumsByOwnerWithPaginationQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindAlbumsByOwnerWithPaginationQuery $query): PaginatorInterface
    {
        if (!$query->isAdmin && !$query->ownerUuid->equals($query->requesterUuid)) {
            throw new OwnershipForbiddenException();
        }

        return $this->albumReader->findAllByOwnerUuidWithPagination(
            $query->ownerUuid,
            $query->page,
            $query->limit,
            $query->sortBy,
            $query->sortOrder,
            $query->isFavorite,
            $query->genre,
            $query->search,
            $query->artist,
            $query->format,
            $query->label,
            $query->yearFrom,
            $query->yearTo,
        );
    }
}
