<?php

namespace App\User\Infra\Repository\DoctrineORM;

use App\User\Domain\Exception\MissingDiscogsCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\DiscogsCredentialsReaderInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use App\User\Infra\Security\SecurityUser;
use App\User\Infra\SodiumTokenCipherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DiscogsCredentialsReader implements DiscogsCredentialsReaderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private SodiumTokenCipherInterface $cipher,
    ) {
    }

    public function getDecryptedToken(Uuid $uuid): DiscogsAccessToken
    {
        $user = $this->em->getRepository(SecurityUser::class)->findOneBy(['uuid' => $uuid]);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        $token = $user->getDiscogsAccessToken();

        if (null === $token) {
            throw new MissingDiscogsCredentialsException();
        }

        $nonce = $user->getDiscogsAccessTokenNonce();

        if (null === $nonce) {
            throw new MissingDiscogsCredentialsException();
        }

        return $this->cipher->decrypt($token, $nonce);
    }
}
