<?php

namespace App\User\App\Query;

readonly class FindUserByShareTokenQuery
{
    public function __construct(
        public string $shareToken,
    ) {
    }

    public static function withToken(string $shareToken): self
    {
        return new self($shareToken);
    }
}
