<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use App\Collection\Domain\Album;
use App\Factory\SecurityUserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Uid\UuidV7;

class AlbumControllerTest extends ControllerTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = $this->createJWTAuthenticatedClient();
    }

    #[Test]
    public function retrieveAlbum()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $uuid = $this->createAlbumOwnedBy($user);

        $client->request('GET', '/api/albums/'.$uuid->toString());
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame('Animal Magic', $data['title']);
        $this->assertSame('Bonobo', $data['artist']);
        $this->assertSame(2000, $data['releaseYear']);
        $this->assertSame('Vinyle', $data['format']);
        $this->assertSame('Trip Hop', $data['genre']);
        $this->assertSame('Ninja Tune', $data['label']);
        $this->assertSame('https://example.com/cover.jpg', $data['coverUrl']);
    }

    #[Test]
    public function retrieveAlbumNotFound()
    {

        $this->client->request('GET', '/api/albums/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function createAlbum()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://example.com/cover.jpg',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    #[Test]
    public function createAlbumIgnoreMaliciousPayloadOwner()
    {

        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $client->request('POST', '/api/albums', content: json_encode([
            'ownerUuid' => '019c5ba0-f86b-7594-ac10-b46cb4ee15cc',
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://example.com/cover.jpg',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($client->getResponse()->headers->has('Location'));

        $location = $client->getResponse()->headers->get('Location');
        $albumUuid = UuidV7::fromString(basename($location));

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $album = $em->getRepository(Album::class)->findOneBy(['uuid' => $albumUuid]);
        $this->assertInstanceOf(Album::class, $album);
        $this->assertSame($user->getUuid()->toString(), $album->getOwnerUuid()->toString());
    }

    #[Test]
    public function createAlbumUseAuthenticateUserAsOwner()
    {
        $ownerUuid = UuidV7::fromString('019c5694-5ea9-7aeb-bc79-4baab19c82ad');

        $customUser = SecurityUserFactory::createOne([
            'uuid' => $ownerUuid,
            'email' => 'custom@example.com',
            'password' => 'password',
            'roles' => ['ROLE_USER'],
        ]);

        $jwtManager = $this->client->getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($customUser);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title'       => 'Dummy',
            'artist'      => 'Portishead',
            'releaseYear' => 1994,
            'format'      => 'CD',
            'genre'       => 'Trip Hop',
            'label'       => 'Go! Discs',
            'coverUrl'    => 'https://example.com/dummy.jpg',
        ]));

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $albumUuid = UuidV7::fromString(basename($location));

        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $album = $em->getRepository(Album::class)->findOneBy(['uuid' => $albumUuid]);
        $this->assertInstanceOf(Album::class, $album);
        $this->assertSame($ownerUuid->toString(), $album->getOwnerUuid()->toString());
    }

    #[Test]
    public function updateAlbum()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $uuid = $this->createAlbumOwnedBy($user);

        $client->request('PUT', '/api/albums/'.$uuid, content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'isFavorite' => true,
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://example.com/cover.jpg',
        ]));

        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/albums/'.$uuid);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($data['isFavorite']);
        $this->assertSame('Animal Magic', $data['title']);
        $this->assertSame('Bonobo', $data['artist']);
        $this->assertSame(2000, $data['releaseYear']);
    }

    #[Test]
    public function deleteAlbum()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $uuid = $this->createAlbumOwnedBy($user);

        $client->request('DELETE', '/api/albums/'.$uuid);
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/albums/'.$uuid);
        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function createAlbumReturnsLocationHeader()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'genre' => 'Trip Hop',
            'label' => 'Ninja Tune',
            'coverUrl' => 'https://example.com/cover.jpg',
        ]));

        $this->assertResponseStatusCodeSame(201);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression(
            '#api/albums/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}#',
            $location
        );
    }

    #[Test]
    public function createAlbumWithoutOptionalFields()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    #[Test]
    public function createAlbumWithMissingRequiredFieldReturnsValidationError()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => '',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function createAlbumWithInvalidReleaseYearReturnsValidationError()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => '1800',
            'format' => 'Vinyle',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function createAlbumWithInvalidCoverUrlReturnsValidationError()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'coverUrl' => 'not-a-url',
        ]));

        $this->assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function createAlbumWithInvalidJsonReturns400()
    {

        $this->client->request('POST', '/api/albums', content: '{invalid');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function createAlbumWithEmptyBodyReturns400()
    {

        $this->client->request('POST', '/api/albums', content: '');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function createAlbumWithNonNumericReleaseYearReturnsValidationError()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 'abc',
            'format' => 'Vinyle',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);

        $fields = array_column($data['violations'], 'field');
        $this->assertContains('releaseYear', $fields);
    }

    #[Test]
    public function createAlbumWithFloatReleaseYearReturnsValidationError()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 1992.5,
            'format' => 'Vinyle',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);

        $fields = array_column($data['violations'], 'field');
        $this->assertContains('releaseYear', $fields);
    }

    #[Test]
    public function createAlbumWithAllFieldsMissingReturnsValidationErrors()
    {

        $this->client->request('POST', '/api/albums', content: json_encode([]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
        $this->assertNotEmpty($data['violations']);
    }

    #[Test]
    public function updateAlbumNotFound()
    {

        $this->client->request('PUT', '/api/albums/019c1ec8-961c-7802-90e4-b8163542a2cb', content: json_encode([
            'title' => 'Animal Magic',
            'artist' => 'Bonobo',
            'releaseYear' => 2000,
            'format' => 'Vinyle',
            'isFavorite' => false,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function deleteAlbumNotFound()
    {

        $this->client->request('DELETE', '/api/albums/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
    }
}
