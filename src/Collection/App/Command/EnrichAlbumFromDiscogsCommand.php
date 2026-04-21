<?php

namespace App\Collection\App\Command;

use Symfony\Component\Uid\Uuid;

class EnrichAlbumFromDiscogsCommand
{
    public function __construct(
        public Uuid $albumUuid,
        public Uuid $userUuid,
    ) {

    }

    public static function withAlbumUuid(Uuid $albumUuid, Uuid $userUuid): self
    {
        return new self($albumUuid, $userUuid);
    }
}
