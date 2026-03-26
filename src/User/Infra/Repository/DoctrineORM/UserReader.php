<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserReaderInterface;
use App\User\Domain\User;
use App\User\Infra\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

readonly class UserReader implements UserReaderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findUserByUuid(Uuid $uuid): User
    {
        $securityUser = $this->findSecurityUser(['uuid' => $uuid]);

        return $securityUser->toDomain();
    }

    public function findUserByEmail(string $email): User
    {
        $securityUser = $this->findSecurityUser(['email' => $email]);

        return $securityUser->toDomain();
    }

    private function findSecurityUser(array $criteria): SecurityUser
    {
        $securityUser = $this->entityManager->getRepository(SecurityUser::class)->findOneBy($criteria);

        if (null === $securityUser) {
            throw new UserNotFoundException();
        }

        return $securityUser;
    }
}
