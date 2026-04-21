<?php

namespace App\User\Infra;

use App\User\Domain\Exception\SodiumException;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SodiumTokenCipher implements SodiumTokenCipherInterface
{
    public function __construct(
        #[Autowire(env: 'DISCOGS_TOKEN_ENCRYPTION_KEY')]
        #[\SensitiveParameter]
        private string $secretKey,
    ) {
        $decoded = base64_decode($secretKey, true);

        if (false === $decoded) {
            throw new \LogicException('DISCOGS_TOKEN_ENCRYPTION_KEY is not valid base64.');
        }

        if (SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== strlen($decoded)) {
            throw new \LogicException(sprintf('DISCOGS_TOKEN_ENCRYPTION_KEY must be exactly %d bytes long.', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        }

        $this->secretKey = $decoded;
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
