<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindAlbumsByOwnerWithPaginationQuery;
use App\Collection\App\QueryHandler\FindAlbumsByOwnerWithPaginationQueryHandler;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindAlbumsByOwnerWithPaginationQueryHandlerTest extends TestCase
{
    #[Test]
    public function findAlbumsByOwnerQueryHandlerReturnPaginator(): void
    {
        // Arrange
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindAlbumsByOwnerWithPaginationQuery::withOwnerUuid(
            $ownerUuid,
            $requesterUuid,
            false
        );
        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $stubPaginator = $this->createStub(PaginatorInterface::class);

        $mockAlbumReader
            ->expects($this->once())
            ->method('findAllByOwnerUuidWithPagination')
            ->with($ownerUuid, 1, 50, null, null, null, null, null)
            ->willReturn($stubPaginator);

        // Act
        $handler = new FindAlbumsByOwnerWithPaginationQueryHandler($mockAlbumReader);
        $result = $handler($query);

        // Assert
        $this->assertSame($stubPaginator, $result);
    }

    #[Test]
    public function findAlbumsByOwnerWithDifferentUserReturnOwnershipForbiddenException(): void
    {
        // Arrange
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = FindAlbumsByOwnerWithPaginationQuery::withOwnerUuid(
            $ownerUuid,
            $requesterUuid,
            false
        );

        $stubAlbumReader = $this->createStub(AlbumReaderInterface::class);

        // Assert
        $this->expectException(OwnershipForbiddenException::class);

        // Act
        $handler = new FindAlbumsByOwnerWithPaginationQueryHandler($stubAlbumReader);
        $handler($query);
    }
}
