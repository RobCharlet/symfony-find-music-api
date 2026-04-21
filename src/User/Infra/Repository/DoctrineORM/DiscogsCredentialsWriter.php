<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\DiscogsCredentialsWriterInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use App\User\Infra\Security\SecurityUser;
use App\User\Infra\SodiumTokenCipherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DiscogsCredentialsWriter implements DiscogsCredentialsWriterInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private SodiumTokenCipherInterface $cipher,
    ) {
    }

    public function save(Uuid $userUuid, DiscogsAccessToken $discogsAccessToken): void
    {
        $user = $this->em->getRepository(SecurityUser::class)->findOneBy(['uuid' => $userUuid]);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        [$encryptedToken, $nonce] = $this->cipher->encrypt($discogsAccessToken);
        $user->setDiscogsAccessToken($encryptedToken, $nonce);
        $this->em->flush();
    }

    public function clear(Uuid $userUuid): void
    {
        $user = $this->em->getRepository(SecurityUser::class)->findOneBy(['uuid' => $userUuid]);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        $user->clearDiscogsAccessToken();
        $this->em->flush();
    }
}
