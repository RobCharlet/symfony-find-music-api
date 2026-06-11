<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\GenerateShareLinkCommand;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateShareLinkCommandHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
        private UserWriterInterface $userWriter,
    ) {
    }

    /**
     * Idempotent: returns the existing share token, generating one only on first call.
     */
    public function __invoke(GenerateShareLinkCommand $command): string
    {
        $user = $this->userReader->findUserByUuid($command->userUuid);

        $existingToken = $user->getShareToken();

        if (null !== $existingToken) {
            return $existingToken;
        }

        $token = $user->ensureShareToken();

        $this->userWriter->update($user);

        return $token;
    }
}
