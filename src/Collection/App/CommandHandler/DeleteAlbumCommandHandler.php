<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\DeleteAlbumCommand;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteAlbumCommandHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
        private AlbumWriterInterface $albumWriter,
    ) {
    }

    public function __invoke(DeleteAlbumCommand $command): void
    {
        $uuid = $command->uuid;
        $album = $this->albumReader->findByUuid($uuid);

        if (!$command->isAdmin && !$command->ownerUuid->equals($album->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        $this->albumWriter->delete($album);
    }
}
