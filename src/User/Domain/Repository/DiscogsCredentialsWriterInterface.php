<?php

namespace App\User\Domain\Repository;

use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\Uid\Uuid;

interface DiscogsCredentialsWriterInterface
{
    public function save(Uuid $userUuid, DiscogsAccessToken $discogsAccessToken): void;

    public function clear(Uuid $userUuid): void;
}
