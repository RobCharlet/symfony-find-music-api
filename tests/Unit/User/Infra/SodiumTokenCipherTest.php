<?php

namespace App\Tests\Unit\User\Infra;

use App\User\Domain\Exception\SodiumException;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use App\User\Infra\SodiumTokenCipher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SodiumTokenCipherTest extends TestCase
{
    private function newCipher(?string $rawKey = null): SodiumTokenCipher
    {
        $key = $rawKey ?? sodium_crypto_secretbox_keygen();

        return new SodiumTokenCipher(base64_encode($key));
    }

    #[Test]
    public function encryptThenDecryptRestoresOriginalToken(): void
    {
        $cipher = $this->newCipher();
        $token = DiscogsAccessToken::fromString('xYzDiscogsPersonalAccessToken');

        [$encrypted, $nonce] = $cipher->encrypt($token);

        $this->assertNotSame('xYzDiscogsPersonalAccessToken', $encrypted);
        $this->assertSame(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, strlen($nonce));

        $decrypted = $cipher->decrypt($encrypted, $nonce);

        $this->assertSame('xYzDiscogsPersonalAccessToken', $decrypted->value());
    }

    #[Test]
    public function encryptProducesDifferentCiphertextEachCallDueToNonce(): void
    {
        $cipher = $this->newCipher();
        $token = DiscogsAccessToken::fromString('xYzDiscogsPersonalAccessToken');

        [$encrypted1, $nonce1] = $cipher->encrypt($token);
        [$encrypted2, $nonce2] = $cipher->encrypt($token);

        $this->assertNotSame($encrypted1, $encrypted2);
        $this->assertNotSame($nonce1, $nonce2);
    }

    #[Test]
    public function decryptThrowsWhenKeyDoesNotMatch(): void
    {
        $encryptKey = sodium_crypto_secretbox_keygen();
        $decryptKey = sodium_crypto_secretbox_keygen();

        $encryptCipher = $this->newCipher($encryptKey);
        $decryptCipher = $this->newCipher($decryptKey);

        [$encrypted, $nonce] = $encryptCipher->encrypt(DiscogsAccessToken::fromString('xYzDiscogsToken'));

        $this->expectException(SodiumException::class);

        $decryptCipher->decrypt($encrypted, $nonce);
    }

    #[Test]
    public function decryptThrowsWhenCiphertextIsTampered(): void
    {
        $cipher = $this->newCipher();
        [$encrypted, $nonce] = $cipher->encrypt(DiscogsAccessToken::fromString('xYzDiscogsToken'));

        $tampered = $encrypted;
        $tampered[0] = 'A' === $tampered[0] ? 'B' : 'A';

        $this->expectException(SodiumException::class);

        $cipher->decrypt($tampered, $nonce);
    }

    #[Test]
    public function constructorThrowsWhenKeyIsNotValidBase64(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('DISCOGS_TOKEN_ENCRYPTION_KEY is not valid base64.');

        new SodiumTokenCipher('***not-base64***');
    }

    #[Test]
    public function constructorThrowsWhenDecodedKeyLengthIsInvalid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'DISCOGS_TOKEN_ENCRYPTION_KEY must be exactly %d bytes long.',
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES
            )
        );

        new SodiumTokenCipher(base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES - 1)));
    }
}
