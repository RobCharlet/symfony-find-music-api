<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\User\App\Query\FindPublicProfileQuery;
use App\User\App\QueryHandler\FindPublicProfileQueryHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindPublicProfileQueryHandlerTest extends TestCase
{
    #[Test]
    public function returnsPublicProfile(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $publicUser = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER'], true);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->with($uuid)->willReturn($publicUser);

        $handler = new FindPublicProfileQueryHandler($mockReader);
        $result = $handler(FindPublicProfileQuery::withUuid($uuid));

        $this->assertSame($publicUser, $result);
        $this->assertTrue($result->isPublic());
    }

    #[Test]
    public function nonPublicProfileIsHiddenAsNotFound(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $privateUser = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER'], false);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->with($uuid)->willReturn($privateUser);

        $this->expectException(UserNotFoundException::class);

        $handler = new FindPublicProfileQueryHandler($mockReader);
        $handler(FindPublicProfileQuery::withUuid($uuid));
    }

    #[Test]
    public function missingUserThrowsNotFound(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $handler = new FindPublicProfileQueryHandler($mockReader);
        $handler(FindPublicProfileQuery::withUuid($uuid));
    }
}
