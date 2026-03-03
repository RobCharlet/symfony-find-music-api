<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindExternalReferencesQuery;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindExternalReferencesQueryHandler
{
    public function __construct(
        private ExternalReferenceReaderInterface $externalReferenceReader,
    ) {
    }

    public function __invoke(FindExternalReferencesQuery $query): PaginatorInterface
    {
        return $this->externalReferenceReader->findAll($query->page, $query->limit);
    }
}
