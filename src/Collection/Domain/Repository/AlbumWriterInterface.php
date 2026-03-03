<?php

namespace App\Collection\Domain\Repository;

use App\Collection\Domain\Album;

interface AlbumWriterInterface
{
    public function save(Album $album): void;

    public function delete(Album $album): void;
}
