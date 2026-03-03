<?php

namespace App\Collection\Infra;

use App\Collection\Domain\PaginatorInterface;
use Pagerfanta\Pagerfanta;

class Paginator extends Pagerfanta implements PaginatorInterface
{
    public function getTotalPages(): int
    {
        return $this->getNbPages();
    }

    public function getTotalItems(): int
    {
        return $this->getNbResults();
    }
}
