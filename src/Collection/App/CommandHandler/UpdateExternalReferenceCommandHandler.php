<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\UpdateExternalReferenceCommand;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateExternalReferenceCommandHandler
{
    public function __construct(
        private ExternalReferenceReaderInterface $reader,
        private ExternalReferenceWriterInterface $writer,
    ) {
    }

    public function __invoke(UpdateExternalReferenceCommand $command): void
    {
        $uuid = $command->uuid;
        $externalReference = $this->reader->findByUuid($uuid);

        if (!$command->isAdmin
            && !$command->ownerUuid->equals($externalReference->getAlbum()->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        $externalReference->update(
            PlatformEnum::from($command->platform),
            $command->externalId,
            $command->metadata ?? null
        );

        $this->writer->save($externalReference);
    }
}
