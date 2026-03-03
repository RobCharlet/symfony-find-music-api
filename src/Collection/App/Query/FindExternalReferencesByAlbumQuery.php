<?php

namespace App\Collection\App\Query;

use Symfony\Component\Uid\Uuid;

final readonly class FindExternalReferencesByAlbumQuery
{
    public function __construct(
        public Uuid $albumUuid,
    ) {
    }

    public static function withAlbumUuid(Uuid $albumUuid): self
    {
        return new self($albumUuid);
    }
}
