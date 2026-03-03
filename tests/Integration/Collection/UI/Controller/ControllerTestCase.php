<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use App\Collection\Domain\PlatformEnum;
use App\Factory\AlbumFactory;
use App\Factory\ExternalReferenceFactory;
use App\Factory\SecurityUserFactory;
use App\Tests\JWTAuthenticatedClientTrait;
use App\User\Infra\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;

abstract class ControllerTestCase extends WebTestCase
{
    use Factories;
    use JWTAuthenticatedClientTrait;

    protected function createAuthenticatedClientWithUser(array $roles = ['ROLE_USER']): array
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $user = SecurityUserFactory::createOne([
            'email' => 'customUser@test.com',
            'password' => 'password',
            'roles' => $roles,
        ]);

        $jwtManager = $client->getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        return [$client, $user];
    }

    protected function createAlbumOwnedBy(SecurityUser $user, array $overrides = []): UuidV7
    {
        return $this->createAlbumOwnedByAndReturnAlbum($user, $overrides)['uuid'];
    }

    protected function createAlbumOwnedByAndReturnAlbum(SecurityUser $user, array $overrides = []): array
    {
        $uuid = ($overrides['uuid'] ?? null) instanceof UuidV7 ? $overrides['uuid'] : UuidV7::v7();

        $album = AlbumFactory::createOne(array_merge([
            'uuid' => $uuid,
            'ownerUuid' => $user->getUuid(),
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://example.com/cover.jpg',
        ], $overrides));

        return ['uuid' => $uuid, 'album' => $album];
    }

    protected function createExternalReferencesWithAlbumOwnedByAndReturnExternalReferences(
        SecurityUser $user,
        array $overrides = [],
    ): array {
        $album = $this->createAlbumOwnedByAndReturnAlbum($user, $overrides)['album'];

        $externalReferences = ExternalReferenceFactory::createOne([
            'uuid'       => UuidV7::fromString($album->getUuid()),
            'album'      => $album,
            'platform'   => PlatformEnum::Spotify,
            'externalId' => 'spotify-123',
            'metadata'   => null,
        ]);

        return ['uuid' => $externalReferences->getUuid(), 'externalReference' => $externalReferences];
    }
}
