<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindAlbumsByOwnerWithPaginationQuery
{
    public function __construct(
        public Uuid $ownerUuid,
        public Uuid $requesterUuid,
        public bool $isAdmin,
        public int $page,
        public int $limit,
        public ?string $sortBy,
        public ?string $sortOrder,
        public ?bool $isFavorite,
        public ?string $genre,
        public ?string $search,
    ) {
    }

    public static function withOwnerUuid(
        Uuid $ownerUuid,
        Uuid $requesterUuid,
        bool $isAdmin,
        int $page = 1,
        int $limit = 50,
        ?string $sortBy = null,
        ?string $sortOrder = null,
        ?bool $isFavorite = null,
        ?string $genre = null,
        ?string $search = null,
    ): self {
        return new self(
            $ownerUuid,
            $requesterUuid,
            $isAdmin,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $isFavorite,
            $genre,
            $search
        );
    }
}
