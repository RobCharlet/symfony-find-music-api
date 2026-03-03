<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\AddAlbumCommand;
use App\Collection\Domain\Album;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddAlbumCommandHandler
{
    public function __construct(
        private AlbumWriterInterface $albumWriter,
    ) {
    }

    public function __invoke(AddAlbumCommand $command): void
    {
        $album = new Album(
            $command->uuid,
            $command->ownerUuid,
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
