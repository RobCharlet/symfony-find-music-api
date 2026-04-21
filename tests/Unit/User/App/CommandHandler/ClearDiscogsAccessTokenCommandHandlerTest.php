<?php

namespace App\Tests\Unit\User\App\CommandHandler;

use App\User\App\Command\ClearDiscogsAccessTokenCommand;
use App\User\App\CommandHandler\ClearDiscogsAccessTokenCommandHandler;
use App\User\Domain\Repository\DiscogsCredentialsWriterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class ClearDiscogsAccessTokenCommandHandlerTest extends TestCase
{
    #[Test]
    public function clearsCredentialsForUser(): void
    {
        $userUuid = UuidV7::fromString('019c2e97-8e0e-776c-bf55-76a2765e369d');

        $writer = $this->createMock(DiscogsCredentialsWriterInterface::class);
        $writer->expects($this->once())
            ->method('clear')
            ->with($userUuid);

        $handler = new ClearDiscogsAccessTokenCommandHandler($writer);

        $handler(ClearDiscogsAccessTokenCommand::withUserUuid($userUuid));
    }
}
