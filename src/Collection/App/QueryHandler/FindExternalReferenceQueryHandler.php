<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferenceQuery;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindExternalReferenceQueryHandler
{
    public function __construct(
        private ExternalReferenceReaderInterface $externalReferenceReader,
    ) {
    }

    public function __invoke(FindExternalReferenceQuery $query): ExternalReference
    {
        $externalReference = $this->externalReferenceReader->findByUuid($query->uuid);

        if (!$query->isAdmin
            && !$query->ownerUuid->equals($externalReference->getAlbum()->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        return $externalReference;
    }
}
