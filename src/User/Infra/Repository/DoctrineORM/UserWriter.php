<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserWriterInterface;
use App\User\Domain\User;
use App\User\Infra\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

readonly class UserWriter implements UserWriterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function delete(User $user): void
    {
        $securityUser = $this->getSecurityUser($user->getUuid());
        $this->entityManager->remove($securityUser);
        $this->entityManager->flush();
    }

    public function save(User $user): void
    {
        $securityUser = SecurityUser::fromDomain($user);
        $this->entityManager->persist($securityUser);
        $this->entityManager->flush();
    }

    public function update(User $user): void
    {
        $securityUser = $this->getSecurityUser($user->getUuid());
        $securityUser->updateFromDomain($user);

        $this->entityManager->persist($securityUser);
        $this->entityManager->flush();
    }

    private function getSecurityUser(Uuid $uuid): SecurityUser
    {
        $securityUser = $this->entityManager->getRepository(SecurityUser::class)->findOneBy(['uuid' => $uuid]);

        if (null === $securityUser) {
            throw new UserNotFoundException();
        }

        return $securityUser;
    }
}
