<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\GenerateShareLinkCommand;
use App\User\App\CommandHandler\GenerateShareLinkCommandHandler;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class GenerateShareLinkCommandHandlerTest extends TestCase
{
    #[Test]
    public function generatesAndPersistsTokenOnFirstCall(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $user = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER']);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->with($uuid)->willReturn($user);

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter->expects($this->once())
            ->method('claimShareToken')
            ->with($uuid, $this->matchesRegularExpression('/^[0-9a-f]{32}$/'))
            ->willReturnArgument(1);

        $handler = new GenerateShareLinkCommandHandler($mockReader, $mockWriter);
        $token = $handler(GenerateShareLinkCommand::forUser($uuid));

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
        $this->assertSame($token, $user->getShareToken());
    }

    #[Test]
    public function concurrentLoserReceivesWinnersToken(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $winnerToken = 'b4dc0de4217a9b3d2e8f6041c2b7d9a3';
        $user = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER']);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->with($uuid)->willReturn($user);

        // Another request claimed a token between the read and the write: the DB value wins.
        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter->expects($this->once())->method('claimShareToken')->willReturn($winnerToken);

        $handler = new GenerateShareLinkCommandHandler($mockReader, $mockWriter);
        $token = $handler(GenerateShareLinkCommand::forUser($uuid));

        $this->assertSame($winnerToken, $token);
    }

    #[Test]
    public function returnsExistingTokenWithoutPersisting(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $existingToken = 'c0ffee5417a9b3d2e8f6041c2b7d9a31';
        $user = new User($uuid, 'miles@example.com', 'hashed', ['ROLE_USER'], false, $existingToken);

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->with($uuid)->willReturn($user);

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter->expects($this->never())->method('claimShareToken');

        $handler = new GenerateShareLinkCommandHandler($mockReader, $mockWriter);
        $token = $handler(GenerateShareLinkCommand::forUser($uuid));

        $this->assertSame($existingToken, $token);
    }

    #[Test]
    public function missingUserPropagates(): void
    {
        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');

        $mockReader = $this->createMock(UserReaderInterface::class);
        $mockReader->expects($this->once())->method('findUserByUuid')->willThrowException(new UserNotFoundException());

        $mockWriter = $this->createMock(UserWriterInterface::class);
        $mockWriter->expects($this->never())->method('claimShareToken');

        $this->expectException(UserNotFoundException::class);

        $handler = new GenerateShareLinkCommandHandler($mockReader, $mockWriter);
        $handler(GenerateShareLinkCommand::forUser($uuid));
    }
}
