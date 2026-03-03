<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use App\Collection\Domain\Album;
use App\Collection\Domain\PlatformEnum;
use App\Factory\ExternalReferenceFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Uid\UuidV7;

class ExternalReferenceControllerTest extends ControllerTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = $this->createJWTAuthenticatedClient();
    }

    #[Test]
    public function retrieveExternalReference()
    {
        $albumUuid  = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        $extRefUuid = '019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f';

        [$client, $album] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        ExternalReferenceFactory::createOne([
            'uuid'       => UuidV7::fromString($extRefUuid),
            'album'      => $album,
            'platform'   => PlatformEnum::Spotify,
            'externalId' => 'spotify-123',
            'metadata'   => ['url' => 'https://open.spotify.com/album/123'],
        ]);

        $client->request('GET', '/api/external-references/'.$extRefUuid);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame('spotify', $data['platform']);
        $this->assertSame('spotify-123', $data['externalId']);
        $this->assertSame($albumUuid, $data['albumUuid']);
    }

    #[Test]
    public function retrieveExternalReferenceNotFound()
    {
        $this->client->request('GET', '/api/external-references/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function createExternalReference()
    {
        $albumUuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        [$client] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        $client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => $albumUuid,
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
            'metadata'   => ['url' => 'https://open.spotify.com/album/123'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($client->getResponse()->headers->has('Location'));
    }

    #[Test]
    public function updateExternalReference()
    {
        $albumUuid  = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        $extRefUuid = '019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f';

        [$client, $album] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        ExternalReferenceFactory::createOne([
            'uuid'       => UuidV7::fromString($extRefUuid),
            'album'      => $album,
            'platform'   => PlatformEnum::Spotify,
            'externalId' => 'spotify-123',
            'metadata'   => null,
        ]);

        $client->request('PUT', '/api/external-references/'.$extRefUuid, content: json_encode([
            'platform'   => 'discogs',
            'externalId' => 'discogs-456',
            'metadata'   => ['url' => 'https://www.discogs.com/release/456'],
        ]));

        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/external-references/'.$extRefUuid);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame('discogs', $data['platform']);
        $this->assertSame('discogs-456', $data['externalId']);
    }

    #[Test]
    public function deleteExternalReference()
    {
        $albumUuid  = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        $extRefUuid = '019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f';

        [$client, $album] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        ExternalReferenceFactory::createOne([
            'uuid'       => UuidV7::fromString($extRefUuid),
            'album'      => $album,
            'platform'   => PlatformEnum::Spotify,
            'externalId' => 'spotify-123',
            'metadata'   => null,
        ]);

        $client->request('DELETE', '/api/external-references/'.$extRefUuid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/external-references/'.$extRefUuid);
        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function createExternalReferenceReturnsLocationHeader()
    {
        $albumUuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        [$client] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        $client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => $albumUuid,
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
            'metadata'   => ['url' => 'https://open.spotify.com/album/123'],
        ]));

        $this->assertResponseStatusCodeSame(201);

        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression(
            '#api/external-references/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}#',
            $location
        );
    }

    #[Test]
    public function createExternalReferenceWithoutMetadata()
    {
        $albumUuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        [$client] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        $client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => $albumUuid,
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    #[Test]
    public function createExternalReferenceWithMissingPlatformReturnsValidationError()
    {
        $albumUuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        [$client] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        $client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => $albumUuid,
            'externalId' => 'spotify-123',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function createExternalReferenceWithInvalidPlatformReturnsValidationError()
    {
        $albumUuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        [$client] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        $client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => $albumUuid,
            'platform'   => 'invalid_platform',
            'externalId' => 'ref-123',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function updateExternalReferenceNotFound()
    {
        $this->client->request('PUT', '/api/external-references/019c1ec8-961c-7802-90e4-b8163542a2cb', content: json_encode([
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function updateExternalReferenceWithMissingPlatformReturnsValidationError()
    {
        $albumUuid  = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        $extRefUuid = '019c2e97-b1a2-7d3e-9f44-1a2b3c4d5e6f';

        [$client, $album] = $this->createAuthenticatedClientWithAlbum($albumUuid);

        ExternalReferenceFactory::createOne([
            'uuid'       => UuidV7::fromString($extRefUuid),
            'album'      => $album,
            'platform'   => PlatformEnum::Spotify,
            'externalId' => 'spotify-123',
            'metadata'   => null,
        ]);

        $client->request('PUT', '/api/external-references/'.$extRefUuid, content: json_encode([
            'externalId' => 'new-id-456',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function createExternalReferenceWithInvalidAlbumUuidReturnsValidationError()
    {
        $this->client->request('POST', '/api/external-references', content: json_encode([
            'albumUuid'  => 'not-a-uuid',
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);

        $fields = array_column($data['violations'], 'field');
        $this->assertContains('albumUuid', $fields);
    }

    #[Test]
    public function createExternalReferenceWithMissingAlbumUuidReturnsValidationError()
    {
        $this->client->request('POST', '/api/external-references', content: json_encode([
            'platform'   => 'spotify',
            'externalId' => 'spotify-123',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function createExternalReferenceWithInvalidJsonReturns400()
    {
        $this->client->request('POST', '/api/external-references', content: '{invalid');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function deleteExternalReferenceNotFound()
    {
        $this->client->request('DELETE', '/api/external-references/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return array{0: KernelBrowser, 1: Album}
     */
    private function createAuthenticatedClientWithAlbum(string $albumUuid): array
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $uuid = UuidV7::fromString($albumUuid);

        $result = $this->createAlbumOwnedByAndReturnAlbum($user, [
            'uuid' => $uuid,
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
        ]);

        $album = $result['album'];

        return [$client, $album];
    }
}
