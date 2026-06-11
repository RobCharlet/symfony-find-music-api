<?php

namespace App\Tests\Integration\User\UI\Controller;

use App\Factory\AlbumFactory;
use App\Factory\SecurityUserFactory;
use App\Tests\JWTAuthenticatedClientTrait;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\UuidV7;

class SharedCollectionControllerTest extends WebTestCase
{
    use JWTAuthenticatedClientTrait;

    #[Test]
    public function authenticatedUserGeneratesIdempotentShareLink(): void
    {
        $client = $this->createJWTAuthenticatedClient();

        $client->request('POST', '/api/users/me/share-link');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $data['token']);
        $this->assertSame('/api/shared/'.$data['token'], $data['url']);

        $client->request('POST', '/api/users/me/share-link');

        $this->assertResponseStatusCodeSame(200);
        $second = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($data['token'], $second['token']);
    }

    #[Test]
    public function anonymousCannotGenerateShareLink(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/users/me/share-link');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function anonymousCanReadSharedCollectionOfPrivateUser(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $token = 'c0ffee5417a9b3d2e8f6041c2b7d9a31';
        SecurityUserFactory::createOne([
            'uuid'       => $uuid,
            'email'      => 'miles@example.com',
            'roles'      => ['ROLE_USER'],
            'isPublic'   => false,
            'shareToken' => $token,
        ]);
        AlbumFactory::createOne([
            'ownerUuid' => $uuid,
            'title'     => 'Kind of Blue',
            'artist'    => 'Miles Davis',
            'rating'    => 5,
            'personalNote' => 'Private listening note',
        ]);
        AlbumFactory::createOne([
            'ownerUuid' => $uuid,
            'title'     => 'Bitches Brew',
            'artist'    => 'Miles Davis',
        ]);

        $client->request('GET', '/api/shared/'.$token.'?limit=1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Miles Davis', $data['data'][0]['artist']);
        $this->assertArrayNotHasKey('ownerUuid', $data['data'][0]);
        $this->assertArrayNotHasKey('rating', $data['data'][0]);
        $this->assertArrayNotHasKey('personalNote', $data['data'][0]);
        $this->assertSame(1, $data['pagination']['maxPerPage']);
        $this->assertSame(2, $data['pagination']['totalItems']);
    }

    #[Test]
    public function unknownShareTokenReturns404(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/shared/deadbeefdeadbeefdeadbeefdeadbeef');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }
}
