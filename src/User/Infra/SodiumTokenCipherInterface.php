<?php

namespace App\User\Infra;

use App\User\Domain\ValueObject\DiscogsAccessToken;

interface SodiumTokenCipherInterface
{
    public function encrypt(DiscogsAccessToken $token): array;

    public function decrypt(string $encryptedToken, string $nonce): DiscogsAccessToken;
}
