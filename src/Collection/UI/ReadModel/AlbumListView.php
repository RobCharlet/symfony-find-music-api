<?php

namespace App\Collection\UI\ReadModel;

use App\Collection\Domain\PaginatorInterface;
use App\Shared\App\DTO\PaginationDTO;
use Symfony\Component\JsonStreamer\Attribute\JsonStreamable;

/**
 * Paginated album list response streamed via JsonStreamer.
 */
#[JsonStreamable]
class AlbumListView
{
    /**
     * @param list<AlbumView> $data
     */
    public function __construct(
        public array $data,
        public PaginationDTO $pagination,
    ) {
    }

    public static function fromPaginator(PaginatorInterface $paginator): self
    {
        $albums = [];
        foreach ($paginator as $album) {
            $albums[] = AlbumView::fromAlbum($album);
        }

        return new self($albums, PaginationDTO::fromPaginator($paginator));
    }
}
