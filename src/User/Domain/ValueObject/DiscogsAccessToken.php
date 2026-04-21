<?php

namespace App\User\Domain\ValueObject;

use App\User\Domain\Exception\InvalidDiscogsAccessTokenException;

readonly class DiscogsAccessToken
{
    private function __construct(
        #[\SensitiveParameter]
        private string $value,
    ) {
    }

    public static function fromString(string $rawValue): self
    {
        $trimmedValue = trim($rawValue);
        if (empty($trimmedValue)) {
            throw new InvalidDiscogsAccessTokenException('Discogs access token cannot be empty');
        }
        if (strlen($trimmedValue) > 256) {
            throw new InvalidDiscogsAccessTokenException('Discogs access token cannot be longer than 256 characters');
        }

        return new self($trimmedValue);
    }

    public function value(): string
    {
        return $this->value;
    }
}
