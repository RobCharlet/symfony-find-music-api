<?php

namespace App\Collection\App\Query;

final readonly class FindCollectionQuery
{
    public function __construct(
        public int $page,
        public int $limit,
    ) {
    }

    public static function withPageAndLimit(int $page = 1, int $limit = 50): self
    {
        return new self($page, $limit);
    }
}
