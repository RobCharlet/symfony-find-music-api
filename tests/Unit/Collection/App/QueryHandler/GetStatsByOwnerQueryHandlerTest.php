<?php

namespace App\Tests\Unit\Collection\App\QueryHandler;

use App\Collection\App\Query\GetStatsByOwnerQuery;
use App\Collection\App\QueryHandler\GetStatsByOwnerQueryHandler;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class GetStatsByOwnerQueryHandlerTest extends TestCase
{
    #[Test]
    public function getStatsByOwnerReturnsStatsArray(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $query = GetStatsByOwnerQuery::withOwnerUuid($ownerUuid, $requesterUuid, false);

        $expectedStats = [
            'totalAlbums' => 5,
            'genres' => ['Rock' => 3, 'Jazz' => 2],
            'formats' => ['Vinyle' => 4, 'CD' => 1],
            'releaseYears' => [1995 => 2, 2003 => 3],
            'labels' => ['Blue Note' => 2, 'Sub Pop' => 3],
        ];

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findStatsByOwnerUuid')
            ->with($ownerUuid)
            ->willReturn($expectedStats);

        $handler = new GetStatsByOwnerQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($expectedStats, $result);
    }

    #[Test]
    public function getStatsByOwnerAsAdminReturnsStatsArray(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $adminUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = GetStatsByOwnerQuery::withOwnerUuid($ownerUuid, $adminUuid, true);

        $expectedStats = ['totalAlbums' => 3];

        $mockAlbumReader = $this->createMock(AlbumReaderInterface::class);
        $mockAlbumReader
            ->expects($this->once())
            ->method('findStatsByOwnerUuid')
            ->with($ownerUuid)
            ->willReturn($expectedStats);

        $handler = new GetStatsByOwnerQueryHandler($mockAlbumReader);
        $result = $handler($query);

        $this->assertSame($expectedStats, $result);
    }

    #[Test]
    public function getStatsByOwnerWithDifferentUserThrowsOwnershipForbiddenException(): void
    {
        $ownerUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');
        $requesterUuid = UuidV7::fromString('019cba11-cd78-7ffa-8133-66be4c2ac39a');

        $query = GetStatsByOwnerQuery::withOwnerUuid($ownerUuid, $requesterUuid, false);

        $stubAlbumReader = $this->createStub(AlbumReaderInterface::class);

        $this->expectException(OwnershipForbiddenException::class);

        $handler = new GetStatsByOwnerQueryHandler($stubAlbumReader);
        $handler($query);
    }
}
