<?php

namespace App\Shared\App\DTO;

use App\Collection\Domain\PaginatorInterface;

class PaginationDTO
{
    public function __construct(
        public int $currentPage,
        public int $maxPerPage,
        public int $totalItems,
        public int $totalPages,
        public bool $hasNextPage,
        public bool $hasPreviousPage,
    ) {
    }

    public static function fromPaginator(PaginatorInterface $paginator): self
    {
        return new self(
            $paginator->getCurrentPage(),
            $paginator->getMaxPerPage(),
            $paginator->getTotalItems(),
            $paginator->getTotalPages(),
            $paginator->hasNextPage(),
            $paginator->hasPreviousPage()
        );
    }
}
