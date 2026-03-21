<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferencesByAlbumQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindExternalReferencesByAlbumQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
        private ExternalReferenceReaderInterface $externalReferenceReader,
    ) {
    }

    public function __invoke(FindExternalReferencesByAlbumQuery $query): array
    {
        $album = $this->albumReader->findByUuid($query->albumUuid);

        if (!$query->isAdmin && !$query->requesterUuid->equals($album->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        return $this->externalReferenceReader->findAllByAlbumUuid($query->albumUuid);
    }
}
