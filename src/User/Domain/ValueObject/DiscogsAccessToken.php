<?php

namespace App\User\Domain\ValueObject;

readonly class DiscogsAccessToken
{
    public function __construct(private string $value)
    {
    }

    public static function fromString(string $rawValue): self
    {
        $trimmedValue = trim($rawValue);
        if (empty($trimmedValue)) {
            throw new \InvalidArgumentException('Discogs access token cannot be empty');
        }
        if (strlen($trimmedValue) > 256) {
            throw new \InvalidArgumentException('Discogs access token cannot be longer than 256 characters');
        }

        return new self($trimmedValue);
    }

    public function value(): string
    {
        return $this->value;
    }
}
