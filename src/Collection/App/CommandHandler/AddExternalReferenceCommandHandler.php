<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\AddExternalReferenceCommand;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\UuidV7;

#[AsMessageHandler]
final readonly class AddExternalReferenceCommandHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
        private ExternalReferenceWriterInterface $externalReferenceWriter,
    ) {
    }

    public function __invoke(AddExternalReferenceCommand $command): void
    {
        $album = $this->albumReader->findByUuid(UuidV7::fromString($command->albumUuid));

        $externalReference = new ExternalReference(
            $command->uuid,
            $album,
            PlatformEnum::from($command->platform),
            $command->externalId,
            $command->metadata ?? null
        );

        $this->externalReferenceWriter->save($externalReference);
    }
}
