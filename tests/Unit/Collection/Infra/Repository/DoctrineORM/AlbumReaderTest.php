<?php

namespace App\Tests\Unit\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Infra\Repository\DoctrineORM\AlbumReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

class AlbumReaderTest extends TestCase
{
    #[Test]
    public function findAlbumsByOwnerWithForbiddenSortReturnInvalidArgumentException(): void
    {
        $uuid = UuidV7::fromString('019cf779-ffe6-74b8-8b7d-eb32e016d6a7');
        $stubQueryBuilder = $this->createStub(QueryBuilder::class);
        $stubEntityManager = $this->createStub(EntityManagerInterface::class);
        $stubEntityManager->method('createQueryBuilder')->willReturn($stubQueryBuilder);

        $albumReader = new AlbumReader($stubEntityManager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sortBy: caleçon');

        $albumReader->findAllByOwnerUuidWithPagination($uuid, 1, 10, 'caleçon', null, null, null, null, null, null, null, null, null);
    }

    #[Test]
    public function findAlbumsByOwnerWithForbiddenOrderReturnInvalidArgumentException(): void
    {
        $uuid = UuidV7::fromString('019cf779-ffe6-74b8-8b7d-eb32e016d6a7');
        $stubQueryBuilder = $this->createStub(QueryBuilder::class);
        $stubEntityManager = $this->createStub(EntityManagerInterface::class);
        $stubEntityManager->method('createQueryBuilder')->willReturn($stubQueryBuilder);

        $albumReader = new AlbumReader($stubEntityManager);

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"ABCD" is not a valid backing value for enum App\Collection\Domain\SortDirectionEnum');

        $albumReader->findAllByOwnerUuidWithPagination($uuid, 1, 10, 'title', 'ABCD', null, null, null, null, null, null, null, null);
    }
}
