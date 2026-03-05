<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindCollectionQuery;
use App\Collection\App\QueryHandler\FindCollectionQueryHandler;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FindCollectionQueryHandlerTest extends TestCase
{
    #[Test]
    public function findCollectionQueryHandlerRetrievePaginator(): void
    {
        $query = FindCollectionQuery::withPageAndLimit(1, 50);
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $stubPaginator = $this->createStub(PaginatorInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($stubPaginator);

        $handler = new FindCollectionQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($stubPaginator, $result);
    }
}
