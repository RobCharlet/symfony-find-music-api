<?php

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidDiscogsAccessTokenException;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscogsAccessTokenTest extends TestCase
{
    #[Test]
    public function fromStringBuildsTokenFromRawValue(): void
    {
        $token = DiscogsAccessToken::fromString('xYzDiscogsPersonalAccessToken123');

        $this->assertSame('xYzDiscogsPersonalAccessToken123', $token->value());
    }

    #[Test]
    public function fromStringTrimsWhitespace(): void
    {
        $token = DiscogsAccessToken::fromString("  xYzDiscogsPersonalAccessToken123\n");

        $this->assertSame('xYzDiscogsPersonalAccessToken123', $token->value());
    }

    #[Test]
    public function fromStringThrowsWhenValueIsEmpty(): void
    {
        $this->expectException(InvalidDiscogsAccessTokenException::class);
        $this->expectExceptionMessage('Discogs access token cannot be empty');

        DiscogsAccessToken::fromString('   ');
    }

    #[Test]
    public function fromStringThrowsWhenValueIsTooLong(): void
    {
        $this->expectException(InvalidDiscogsAccessTokenException::class);
        $this->expectExceptionMessage('Discogs access token cannot be longer than 256 characters');

        DiscogsAccessToken::fromString(str_repeat('a', 257));
    }
}
