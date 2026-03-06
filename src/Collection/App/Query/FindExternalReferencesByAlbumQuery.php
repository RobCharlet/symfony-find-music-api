<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindExternalReferencesByAlbumQuery
{
    public function __construct(
        public Uuid $albumUuid,
        public Uuid $requesterUuid,
        public bool $isAdmin,
    ) {
    }

    public static function withAlbumUuid(Uuid $albumUuid, Uuid $requesterUuid, bool $isAdmin): self
    {
        return new self($albumUuid, $requesterUuid, $isAdmin);
    }
}
