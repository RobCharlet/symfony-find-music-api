<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\CreateDiscogsAccessTokenCommand;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use App\User\Infra\Repository\DoctrineORM\DiscogsCredentialsWriter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateDiscogsAccessTokenCommandHandler
{
    public function __construct(
        private DiscogsCredentialsWriter $discogsCredentialsWriter,
    ) {
    }

    public function __invoke(CreateDiscogsAccessTokenCommand $command): void
    {
        // if (!$command->isAdmin &&) {}
        $discogsAccessToken = DiscogsAccessToken::fromString($command->discogsAccessToken);

        $this->discogsCredentialsWriter->save($command->userUuid, $discogsAccessToken);
    }
}
