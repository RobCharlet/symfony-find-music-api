<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\ClearDiscogsAccessTokenCommand;
use App\User\Domain\Repository\DiscogsCredentialsWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ClearDiscogsAccessTokenCommandHandler
{
    public function __construct(
        private DiscogsCredentialsWriterInterface $discogsCredentialsWriter,
    ) {
    }

    public function __invoke(ClearDiscogsAccessTokenCommand $command): void
    {
        $this->discogsCredentialsWriter->clear($command->userUuid);
    }
}
