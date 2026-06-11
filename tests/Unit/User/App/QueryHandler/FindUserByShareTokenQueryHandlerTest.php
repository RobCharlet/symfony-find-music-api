<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\User\App\Query\FindUserByShareTokenQuery;
use App\User\App\QueryHandler\FindUserByShareTokenQueryHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindUserByShareTokenQueryHandlerTest extends TestCase
{
    #[Test]
    public function returnsUserEvenWhenProfileIsPrivate(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $token = 'c0ffee5417a9b3d2e8f6041c2b7d9a31';
        $privateUser = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER'], false, $token);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByShareToken')->with($token)->willReturn($privateUser);

        $handler = new FindUserByShareTokenQueryHandler($mockReader);
        $result = $handler(FindUserByShareTokenQuery::withToken($token));

        $this->assertSame($privateUser, $result);
        $this->assertFalse($result->isPublic());
    }

    #[Test]
    public function unknownTokenPropagatesNotFound(): void
    {
        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByShareToken')->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $handler = new FindUserByShareTokenQueryHandler($mockReader);
        $handler(FindUserByShareTokenQuery::withToken('deadbeefdeadbeefdeadbeefdeadbeef'));
    }
}
