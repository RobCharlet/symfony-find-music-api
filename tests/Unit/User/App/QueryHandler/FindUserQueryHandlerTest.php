<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\User\App\Query\FindUserQuery;
use App\User\App\QueryHandler\FindUserQueryHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindUserQueryHandlerTest extends TestCase
{
    #[Test]
    public function retrievesUserByUuid(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $existingUser = new User($uuid, 'john@example.com', 'hashed', ['ROLE_USER']);

        $query = FindUserQuery::withUuid($uuid);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->willReturn($existingUser);

        $handler = new FindUserQueryHandler($mockReader);
        $result = $handler($query);

        $this->assertSame($existingUser, $result);
    }

    #[Test]
    public function throwsUserNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $query = FindUserQuery::withUuid($uuid);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader
            ->expects($this->once())
            ->method('findUserByUuid')
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $handler = new FindUserQueryHandler($mockReader);
        $handler($query);
    }
}
