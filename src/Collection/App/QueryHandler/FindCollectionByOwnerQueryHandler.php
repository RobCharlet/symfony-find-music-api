<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindCollectionByOwnerQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindCollectionByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindCollectionByOwnerQuery $query): iterable
    {
        if (!$query->isAdmin && !$query->ownerUuid->equals($query->requesterUuid)) {
            throw new OwnershipForbiddenException();
        }

        return $this->albumReader->findAllByOwnerUuid(
            $query->ownerUuid,
        );
    }
}
