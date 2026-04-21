<?php

namespace App\Collection\App\CommandHandler;

use App\Collection\App\Command\EnrichAlbumFromDiscogsCommand;
use App\Collection\Domain\Exception\DiscogsIdException;
use App\Collection\Domain\PlatformEnum;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\Domain\Repository\AlbumReaderInterface;
use App\Collection\Domain\Repository\AlbumWriterInterface;
use App\Collection\Domain\Repository\DiscogsApiClientInterface;
use App\Collection\Domain\Repository\ExternalReferenceReaderInterface;
use App\User\Domain\Repository\DiscogsCredentialsReaderInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class EnrichAlbumFromDiscogsCommandHandler
{
    public function __construct(
        private AlbumReaderInterface $albumReader,
        private AlbumWriterInterface $albumWriter,
        private DiscogsApiClientInterface $discogsApiClient,
        private DiscogsCredentialsReaderInterface $discogsCredentialsReader,
        private ExternalReferenceReaderInterface $externalReferenceReader,
    ) {
    }

    public function __invoke(EnrichAlbumFromDiscogsCommand $command): void
    {
        $album = $this->albumReader->findByUuid($command->albumUuid);

        if (!$command->userUuid->equals($album->getOwnerUuid())) {
            throw new OwnershipForbiddenException();
        }

        $externalReferences = $this->externalReferenceReader->findAllByAlbumUuid($command->albumUuid);

        if ([] === $externalReferences) {
            throw new ExternalReferenceNotFoundException();
        }

        $releaseId = null;

        foreach ($externalReferences as $externalReference) {
            if (PlatformEnum::Discogs === $externalReference->getPlatform()) {
                $releaseId = $externalReference->getExternalId();
            }
        }

        if (null === $releaseId) {
            throw new DiscogsIdException();
        }

        $discogsAccessToken = $this->discogsCredentialsReader->getDecryptedToken($command->userUuid);

        $infos = $this->discogsApiClient->fetchRelease($releaseId, $discogsAccessToken);

        $album->enrich(
            $infos['images'][0]['uri'] ?? null,
            $infos['genres'][0] ?? null,
            $infos['labels'][0]['name'] ?? null,
            $infos['year'] ?? null,
        );

        $this->albumWriter->save($album);

    }
}
