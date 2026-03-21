<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\GetStatsByOwnerQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetStatsByOwnerQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {

    }

    public function __invoke(GetStatsByOwnerQuery $query): array
    {
        if (!$query->isAdmin && !$query->ownerUuid->equals($query->userUuid)) {
            throw new OwnershipForbiddenException();
        }

        return $this->albumReader->findStatsByOwnerUuid($query->ownerUuid);
    }
}
