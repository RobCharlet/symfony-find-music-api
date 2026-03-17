<?php

namespace App\Tests\Unit\Shared\App\DTO;

use App\Collection\Domain\PaginatorInterface;
use App\Shared\App\DTO\PaginationDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaginationDTOTest extends TestCase
{
    #[Test]
    public function fromPaginatorMapsAllFieldsCorrectly(): void
    {
        $paginator = $this->createStub(PaginatorInterface::class);
        $paginator->method('getCurrentPage')->willReturn(3);
        $paginator->method('getMaxPerPage')->willReturn(25);
        $paginator->method('getTotalItems')->willReturn(73);
        $paginator->method('getTotalPages')->willReturn(3);
        $paginator->method('hasNextPage')->willReturn(false);
        $paginator->method('hasPreviousPage')->willReturn(true);

        $dto = PaginationDTO::fromPaginator($paginator);

        $this->assertSame(3, $dto->currentPage);
        $this->assertSame(25, $dto->maxPerPage);
        $this->assertSame(73, $dto->totalItems);
        $this->assertSame(3, $dto->totalPages);
        $this->assertFalse($dto->hasNextPage);
        $this->assertTrue($dto->hasPreviousPage);
    }

    #[Test]
    public function fromPaginatorOnFirstPage(): void
    {
        $paginator = $this->createStub(PaginatorInterface::class);
        $paginator->method('getCurrentPage')->willReturn(1);
        $paginator->method('getMaxPerPage')->willReturn(10);
        $paginator->method('getTotalItems')->willReturn(42);
        $paginator->method('getTotalPages')->willReturn(5);
        $paginator->method('hasNextPage')->willReturn(true);
        $paginator->method('hasPreviousPage')->willReturn(false);

        $dto = PaginationDTO::fromPaginator($paginator);

        $this->assertSame(1, $dto->currentPage);
        $this->assertSame(10, $dto->maxPerPage);
        $this->assertSame(42, $dto->totalItems);
        $this->assertSame(5, $dto->totalPages);
        $this->assertTrue($dto->hasNextPage);
        $this->assertFalse($dto->hasPreviousPage);
    }

    #[Test]
    public function fromPaginatorWithSinglePage(): void
    {
        $paginator = $this->createStub(PaginatorInterface::class);
        $paginator->method('getCurrentPage')->willReturn(1);
        $paginator->method('getMaxPerPage')->willReturn(50);
        $paginator->method('getTotalItems')->willReturn(7);
        $paginator->method('getTotalPages')->willReturn(1);
        $paginator->method('hasNextPage')->willReturn(false);
        $paginator->method('hasPreviousPage')->willReturn(false);

        $dto = PaginationDTO::fromPaginator($paginator);

        $this->assertSame(1, $dto->currentPage);
        $this->assertSame(50, $dto->maxPerPage);
        $this->assertSame(7, $dto->totalItems);
        $this->assertSame(1, $dto->totalPages);
        $this->assertFalse($dto->hasNextPage);
        $this->assertFalse($dto->hasPreviousPage);
    }
}
