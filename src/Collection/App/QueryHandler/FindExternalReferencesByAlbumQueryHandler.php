<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferencesByAlbumQuery;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindExternalReferencesByAlbumQueryHandler
{
    public function __construct(
        private ExternalReferenceReaderInterface $reader,
    ) {
    }

    public function __invoke(FindExternalReferencesByAlbumQuery $query): array
    {
        return $this->reader->findAllByAlbumUuid($query->albumUuid);
    }
}
