<?php

namespace App\Collection\Domain\Repository;

use App\User\Domain\ValueObject\DiscogsAccessToken;

interface DiscogsApiClientInterface
{
    public function fetchRelease(string $releaseId, DiscogsAccessToken $token): array;
}
