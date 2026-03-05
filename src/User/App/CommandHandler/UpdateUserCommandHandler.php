<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\UpdateUserCommand;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\PasswordHasherInterface;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\Repository\UserWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateUserCommandHandler
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private UserReaderInterface $userReader,
        private UserWriterInterface $userWriter,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->userReader->findUserByUuid($command->uuid);

        if (
            !$command->isAdmin
            && !$this->passwordHasher->verify($user, $command->currentPassword ?? '')) {
            throw new InvalidCurrentPasswordException();
        }

        if (null === $command->password) {
            $user->update(
                $command->email,
                $command->roles,
            );
        } else {
            $hashedPassword = $this->passwordHasher->hash($user, $command->password);

            $user->update(
                $command->email,
                $command->roles,
                $hashedPassword,
            );
        }

        $this->userWriter->update($user);
    }
}
