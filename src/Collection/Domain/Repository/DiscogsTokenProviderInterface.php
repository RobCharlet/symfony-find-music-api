<?php

namespace App\Collection\Domain\Repository;

use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\Uid\Uuid;

interface DiscogsTokenProviderInterface
{
    public function getToken(Uuid $uuid): DiscogsAccessToken;
}
