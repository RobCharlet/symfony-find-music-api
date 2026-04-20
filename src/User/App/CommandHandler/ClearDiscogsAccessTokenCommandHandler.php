<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\ClearDiscogsAccessTokenCommand;
use App\User\Infra\Repository\DoctrineORM\DiscogsCredentialsWriter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ClearDiscogsAccessTokenCommandHandler
{
    public function __construct(
        private DiscogsCredentialsWriter $discogsCredentialsWriter,
    ) {
    }

    public function __invoke(ClearDiscogsAccessTokenCommand $command): void
    {
        $this->discogsCredentialsWriter->clear($command->userUuid);
    }
}
