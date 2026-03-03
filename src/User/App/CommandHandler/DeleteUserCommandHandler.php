<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\DeleteUserCommand;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DeleteUserCommandHandler
{
    public function __construct(
        private UserReaderInterface $userReader,
        private UserWriterInterface $userWriter,
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userReader->findUserByUuid($command->uuid);
        $this->userWriter->delete($user);
    }
}
