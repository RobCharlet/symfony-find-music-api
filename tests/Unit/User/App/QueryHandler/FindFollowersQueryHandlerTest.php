<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\Collection\Domain\PaginatorInterface;
use App\User\App\Query\FindFollowersQuery;
use App\User\App\QueryHandler\FindFollowersQueryHandler;
use App\User\Domain\Repository\FollowReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindFollowersQueryHandlerTest extends TestCase
{
    #[Test]
    public function returnsPaginatedFollowers(): void
    {
        $traneUuid = UuidV7::fromString('019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60');
        $paginator = $this->createStub(PaginatorInterface::class);

        $mockFollowReader = $this->createMock(FollowReaderInterface::class);
        $mockFollowReader->expects($this->once())
            ->method('findFollowers')
            ->with($traneUuid, 1, 50)
            ->willReturn($paginator);

        $handler = new FindFollowersQueryHandler($mockFollowReader);

        $this->assertSame($paginator, $handler(FindFollowersQuery::forUser($traneUuid)));
    }
}
