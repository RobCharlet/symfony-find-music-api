<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\DeleteExternalReferenceCommand;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\Collection\Domain\Repository\ExternalReferenceWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteExternalReferenceCommandHandler
{
    public function __construct(
        private ExternalReferenceReaderInterface $reader,
        private ExternalReferenceWriterInterface $writer,
    ) {
    }

    public function __invoke(DeleteExternalReferenceCommand $command): void
    {
        $uuid              = $command->uuid;
        $externalReference = $this->reader->findByUuid($uuid);

        if (!$command->isAdmin
            && !$command->ownerUuid->equals($externalReference->getAlbum()->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        $this->writer->delete($externalReference);
    }
}
