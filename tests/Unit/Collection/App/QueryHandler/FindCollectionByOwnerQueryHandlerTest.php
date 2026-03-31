<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\FindCollectionByOwnerQuery;
use App\Collection\App\QueryHandler\FindCollectionByOwnerQueryHandler;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindCollectionByOwnerQueryHandlerTest extends TestCase
{
    #[Test]
    public function findCollectionByOwnerReturnsIterable(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = FindCollectionByOwnerQuery::withOwnerUuid($ownerUuid, $requesterUuid, false);

        $expectedCollection = new \ArrayIterator([['title' => 'Black Sands']]);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findAllByOwnerUuid')
            ->with($ownerUuid)
            ->willReturn($expectedCollection);

        $handler = new FindCollectionByOwnerQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($expectedCollection, $result);
    }

    #[Test]
    public function findCollectionByOwnerAsAdminReturnsIterable(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $adminUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = FindCollectionByOwnerQuery::withOwnerUuid($ownerUuid, $adminUuid, true);

        $expectedCollection = new \ArrayIterator([]);

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findAllByOwnerUuid')
            ->with($ownerUuid)
            ->willReturn($expectedCollection);

        $handler = new FindCollectionByOwnerQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($expectedCollection, $result);
    }

    #[Test]
    public function findCollectionByOwnerWithDifferentUserThrowsOwnershipForbiddenException(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = FindCollectionByOwnerQuery::withOwnerUuid($ownerUuid, $requesterUuid, false);

        $stubAlbumReader = $this->createStub(AlbumReaderInterface::class);

        $this->expectException(OwnershipForbiddenException::class);

        $handler = new FindCollectionByOwnerQueryHandler($stubAlbumReader);
        $handler($query);
    }
}
