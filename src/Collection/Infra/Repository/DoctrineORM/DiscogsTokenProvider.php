<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Repository\DiscogsTokenProviderInterface;
use App\User\Domain\Repository\DiscogsCredentialsReaderInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\Uid\Uuid;

final readonly class DiscogsTokenProvider implements DiscogsTokenProviderInterface
{
    public function __construct(
        private DiscogsCredentialsReaderInterface $discogsCredentialsReader,
    ) {
    }

    public function getToken(Uuid $uuid): DiscogsAccessToken
    {
        return $this->discogsCredentialsReader->getDecryptedToken($uuid);
    }
}
