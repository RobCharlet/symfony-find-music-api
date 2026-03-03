<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Album;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class AlbumWriter implements AlbumWriterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Album $album): void
    {
        $this->entityManager->persist($album);
        $this->entityManager->flush();
    }

    public function delete(Album $album): void
    {
        $this->entityManager->remove($album);
        $this->entityManager->flush();

    }
}
