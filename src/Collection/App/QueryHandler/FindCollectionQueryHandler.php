<?php

namespace App\Collection\App\QueryHandler;

use App\Collection\App\Query\FindCollectionQuery;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FindCollectionQueryHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
    ) {
    }

    public function __invoke(FindCollectionQuery $query): PaginatorInterface
    {
        return $this->albumReader->findAll($query->page, $query->limit);
    }
}
