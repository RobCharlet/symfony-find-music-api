<?php

namespace App\Collection\Domain\Repository;

use App\Collection\Domain\Album;
use App\Collection\Domain\PaginatorInterface;
use Symfony\Component\Uid\Uuid;

interface AlbumReaderInterface
{
    public function findByUuid(Uuid $uuid): Album;

    public function findAll(int $page, int $limit): PaginatorInterface;

    public function findAllByOwnerUuid(
        Uuid $ownerUuid,
        int $page,
        int $limit,
        ?string $sortBy,
        ?string $sortOrder,
        ?bool $isFavorite,
        ?string $genre,
    ): PaginatorInterface;

    public function findStatsByOwnerUuid(Uuid $ownerUuid): array;
}
