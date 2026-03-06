<?php

namespace App\Tests\Unit\User\App\QueryHandler;

use App\User\App\Query\FindUserQuery;
use App\User\App\QueryHandler\FindUserQueryHandler;
use App\User\Domain\Exception\UserAccessForbiddenException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class FindUserQueryHandlerTest extends TestCase
{
    #[Test]
    public function userCanRetrieveTheirOwnProfile(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $existingUser = new User($uuid, 'john@example.com', 'hashed', ['ROLE_USER']);

        $query = FindUserQuery::withUuid($uuid, $uuid, false);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->willReturn($existingUser);

        $handler = new FindUserQueryHandler($mockReader);
        $result  = $handler($query);

        $this->assertSame($existingUser, $result);
    }

    #[Test]
    public function adminCanRetrieveAnyUser(): void
    {
        $uuid      = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $adminUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $existingUser = new User($uuid, 'john@example.com', 'hashed', ['ROLE_USER']);

        $query = FindUserQuery::withUuid($uuid, $adminUuid, true);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->willReturn($existingUser);

        $handler = new FindUserQueryHandler($mockReader);
        $result  = $handler($query);

        $this->assertSame($existingUser, $result);
    }

    #[Test]
    public function nonAdminCannotRetrieveAnotherUser(): void
    {
        $uuid      = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $otherUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369e');

        $query = FindUserQuery::withUuid($uuid, $otherUuid, false);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->never())->method('findUserByUuid');

        $this->expectException(UserAccessForbiddenException::class);

        $handler = new FindUserQueryHandler($mockReader);
        $handler($query);
    }

    #[Test]
    public function throwsUserNotFoundException(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $query = FindUserQuery::withUuid($uuid, $uuid, false);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $handler = new FindUserQueryHandler($mockReader);
        $handler($query);
    }
}
