<?php

namespace App\User\Domain\Repository;

use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\Uid\Uuid;

interface DiscogsCredentialsReaderInterface
{
    public function getDecryptedToken(Uuid $uuid): DiscogsAccessToken;
}
