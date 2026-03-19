<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumsByOwnerQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindAlbumsByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindAlbumsByOwnerQuery $query): PaginatorInterface
    {
        if (!$query->isAdmin && !$query->ownerUuid->equals($query->requesterUuid)) {
            throw new OwnershipForbiddenException();
        }

        return $this->albumReader->findAllByOwnerUuid(
            $query->ownerUuid,
            $query->page,
            $query->limit,
            $query->sortBy,
            $query->sortOrder,
            $query->isFavorite,
            $query->genre
        );
    }
}
