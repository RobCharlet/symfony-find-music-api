<?php

namespace App\Collection\Domain;

interface PaginatorInterface extends \IteratorAggregate, \Countable
{
    public function getCurrentPage(): int;

    public function getMaxPerPage(): int;

    public function getTotalPages(): int;

    public function getTotalItems(): int;

    public function hasNextPage(): bool;

    public function hasPreviousPage(): bool;
}
