<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumQuery;
use App\Collection\Domain\Album;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindAlbumQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindAlbumQuery $query): Album
    {
        $album = $this->albumReader->findByUuid($query->uuid);

        if (!$query->isAdmin && !$query->ownerUuid->equals($album->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        return $album;
    }
}
