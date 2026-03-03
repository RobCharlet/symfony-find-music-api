<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\UpdateAlbumCommand;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateAlbumCommandHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
        private AlbumWriterInterface $albumWriter,
    ) {
    }

    public function __invoke(UpdateAlbumCommand $command): void
    {
        $uuid = $command->uuid;
        $album = $this->albumReader->findByUuid($uuid);

        if (!$command->isAdmin && !$command->ownerUuid->equals($album->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        $album->update(
            $command->title,
            $command->artist,
            $command->format,
            $command->releaseYear,
            $command->genre,
            $command->label,
            $command->coverUrl,
        );

        $this->albumWriter->save($album);
    }
}
