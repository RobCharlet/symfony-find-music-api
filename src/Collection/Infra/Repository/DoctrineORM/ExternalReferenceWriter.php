<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class ExternalReferenceWriter implements ExternalReferenceWriterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(ExternalReference $externalReference): void
    {
        $this->entityManager->persist($externalReference);
        $this->entityManager->flush();
    }

    public function delete(ExternalReference $externalReference): void
    {
        $this->entityManager->remove($externalReference);
        $this->entityManager->flush();
    }
}
