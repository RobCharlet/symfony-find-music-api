<?php

namespace App\Collection\Domain\Repository;

use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PaginatorInterface;
use Symfony\Component\Uid\Uuid;

interface ExternalReferenceReaderInterface
{
    public function findByUuid(Uuid $uuid): ExternalReference;

    public function findAll(int $page, int $limit): PaginatorInterface;

    public function findAllByAlbumUuid(Uuid $albumUuid): array;

    public function existsByOwnerPlatformExternalId(Uuid $ownerUuid, string $platform, string $externalId): bool;
}
