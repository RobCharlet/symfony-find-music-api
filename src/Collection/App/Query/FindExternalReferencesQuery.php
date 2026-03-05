<?php

namespace App\Collection\App\Query;

final readonly class FindExternalReferencesQuery
{
    public function __construct(
        public ?int $page = 1,
        public ?int $limit = 30,
    ) {
    }

    public static function withPageAndLimit(int $page, int $limit): self
    {
        return new self($page, $limit);
    }
}
