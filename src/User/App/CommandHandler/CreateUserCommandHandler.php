<?php

namespace App\User\App\CommandHandler;

use App\User\App\Command\CreateUserCommand;
use App\User\Domain\PasswordHasherInterface;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateUserCommandHandler
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private UserWriterInterface $userWriter,
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        $user = new User(
            $command->uuid,
            $command->email,
            $command->password,
            $command->roles,
        );

        $plainPassword = $command->password;
        $hashedPassword = $this->passwordHasher->hash($user, $plainPassword);

        $user->setPassword($hashedPassword);

        $this->userWriter->save($user);
    }
}
