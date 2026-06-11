<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\UpdateUserCommand;
use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\UserAccessForbiddenException;
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
        if (!$command->isAdmin && !$command->requesterUuid->equals($command->uuid)) {
            throw new UserAccessForbiddenException();
        }

        $user = $this->userReader->findUserByUuid($command->uuid);

        $emailChanged = null !== $command->email && $command->email !== $user->getEmail();
        $passwordChange = null !== $command->password;
        $rolesChanged = null !== $command->roles && $command->roles !== $user->getRoles();

        // currentPassword is only required for sensitive changes; toggling isPublic alone is ungated.
        if (
            !$command->isAdmin
            && ($emailChanged || $passwordChange || $rolesChanged)
            && !$this->passwordHasher->verify($user, $command->currentPassword ?? '')) {
            throw new InvalidCurrentPasswordException();
        }

        $email = $command->email ?? $user->getEmail();
        $roles = null !== $command->roles ? $command->roles : $user->getRoles();
        $isPublic = $command->isPublic ?? $user->isPublic();

        if (null === $command->password) {
            $user->update(
                $email,
                $roles,
                $isPublic,
            );
        } else {
            $hashedPassword = $this->passwordHasher->hash($user, $command->password);

            $user->update(
                $email,
                $roles,
                $isPublic,
                $hashedPassword,
            );
        }

        $this->userWriter->update($user);
    }
}
