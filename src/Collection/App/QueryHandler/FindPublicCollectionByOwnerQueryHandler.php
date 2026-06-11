<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindPublicCollectionByOwnerQuery;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindPublicCollectionByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    /**
     * Ungated read: profile visibility is enforced upstream by the public profile use case.
     */
    public function __invoke(FindPublicCollectionByOwnerQuery $query): iterable
    {
        return $this->albumReader->findAllByOwnerUuid($query->ownerUuid);
    }
}
