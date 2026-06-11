<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\Collection\Domain\PaginatorInterface;
use App\User\App\Query\FindFollowingQuery;
use App\User\App\QueryHandler\FindFollowingQueryHandler;
use App\User\Domain\Repository\FollowReaderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindFollowingQueryHandlerTest extends TestCase
{
    #[Test]
    public function returnsPaginatedFollowing(): void
    {
        $milesUuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $paginator = $this->createStub(PaginatorInterface::class);

        $mockFollowReader = $this->createMock(FollowReaderInterface::class);
        $mockFollowReader->expects($this->once())
            ->method('findFollowing')
            ->with($milesUuid, 2, 25)
            ->willReturn($paginator);

        $handler = new FindFollowingQueryHandler($mockFollowReader);

        $this->assertSame($paginator, $handler(FindFollowingQuery::forUser($milesUuid, 2, 25)));
    }
}
