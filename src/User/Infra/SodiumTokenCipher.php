<?php

namespace App\User\Infra;

use App\User\Domain\Exception\SodiumException;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SodiumTokenCipher implements SodiumTokenCipherInterface
{
    public function __construct(
        #[Autowire(env: 'DISCOGS_TOKEN_ENCRYPTION_KEY')]
        private string $secretKey,
    ) {
        $this->secretKey = base64_decode($secretKey);
    }

    public function encrypt(DiscogsAccessToken $token): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encryptedMessage = sodium_crypto_secretbox($token->value(), $nonce, $this->secretKey);

        return [$encryptedMessage, $nonce];
    }

    public function decrypt(string $encryptedToken, string $nonce): DiscogsAccessToken
    {
        $decryptedToken = sodium_crypto_secretbox_open($encryptedToken, $nonce, $this->secretKey);

        if (false === $decryptedToken) {
            throw new SodiumException();
        }

        return DiscogsAccessToken::fromString($decryptedToken);
    }
}
