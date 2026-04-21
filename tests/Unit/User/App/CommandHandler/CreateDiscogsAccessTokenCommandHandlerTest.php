<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\CreateDiscogsAccessTokenCommand;
use App\User\App\CommandHandler\CreateDiscogsAccessTokenCommandHandler;
use App\User\Domain\Repository\DiscogsCredentialsWriterInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

class CreateDiscogsAccessTokenCommandHandlerTest extends TestCase
{
    #[Test]
    public function savesEncryptedTokenForUser(): void
    {
        $userUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $writer = $this->createMock(DiscogsCredentialsWriterInterface::class);
        $writer->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Uuid $uuid, DiscogsAccessToken $token) use ($userUuid): void {
                $this->assertTrue($uuid->equals($userUuid));
                $this->assertSame('xYzDiscogsToken', $token->value());
            });

        $handler = new CreateDiscogsAccessTokenCommandHandler($writer);

        $handler(CreateDiscogsAccessTokenCommand::withAccessToken($userUuid, 'xYzDiscogsToken'));
    }

    #[Test]
    public function rejectsEmptyTokenBeforeCallingWriter(): void
    {
        $userUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $writer = $this->createMock(DiscogsCredentialsWriterInterface::class);
        $writer->expects($this->never())->method('save');

        $handler = new CreateDiscogsAccessTokenCommandHandler($writer);

        $this->expectException(\InvalidArgumentException::class);

        $handler(CreateDiscogsAccessTokenCommand::withAccessToken($userUuid, '   '));
    }
}
