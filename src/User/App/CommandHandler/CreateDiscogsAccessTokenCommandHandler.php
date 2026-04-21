<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\CreateDiscogsAccessTokenCommand;
use App\User\Domain\Repository\DiscogsCredentialsWriterInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateDiscogsAccessTokenCommandHandler
{
    public function __construct(
        private DiscogsCredentialsWriterInterface $discogsCredentialsWriter,
    ) {
    }

    public function __invoke(CreateDiscogsAccessTokenCommand $command): void
    {
        $discogsAccessToken = DiscogsAccessToken::fromString($command->discogsAccessToken);

        $this->discogsCredentialsWriter->save($command->userUuid, $discogsAccessToken);
    }
}
