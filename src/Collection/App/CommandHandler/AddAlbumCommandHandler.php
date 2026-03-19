<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\AddAlbumCommand;
use App\Collection\Domain\Album;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddAlbumCommandHandler
{
    public function __construct(
        private AlbumWriterInterface $albumWriter,
        private LoggerInterface $logger,
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
            $command->isFavorite,
            $command->releaseYear,
            $command->genre,
            $command->label,
            $command->coverUrl,
        );

        $this->albumWriter->save($album);
        $this->logger->info('album.created', [
            'uuid'  => $album->getUuid(),
            'owner' => $album->getOwnerUuid(),
            'title' => $album->getTitle(),
        ]);
    }
}
