<?php

namespace App\Collection\App\Query;

final readonly class FindExternalReferencesQuery
{
    public function __construct(
        public int $page,
        public int $limit,
    ) {
    }

    public static function withPageAndLimit(int $page = 1, int $limit = 30): self
    {
        return new self($page, $limit);
    }
}
