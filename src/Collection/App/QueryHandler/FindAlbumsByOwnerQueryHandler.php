<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumsByOwnerQuery;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindAlbumsByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindAlbumsByOwnerQuery $query): array
    {
        return $this->albumReader->findAllByOwnerUuid($query->ownerUuid);
    }
}
