<?php

namespace App\Tests\Integration\User\UI\Controller;

use App\Factory\AlbumFactory;
use App\Factory\SecurityUserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\UuidV7;

class ProfileControllerTest extends WebTestCase
{
    #[Test]
    public function anonymousCanReadPublicProfileWithCollection(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        SecurityUserFactory::createOne([
            'uuid'     => $uuid,
            'email'    => 'miles@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => true,
        ]);
        AlbumFactory::createOne([
            'ownerUuid' => $uuid,
            'title'     => 'Kind of Blue',
            'artist'    => 'Miles Davis',
        ]);

        $client->request('GET', '/api/profiles/'.$uuid->toRfc4122());

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($uuid->toRfc4122(), $data['profile']['uuid']);
        $this->assertTrue($data['profile']['isPublic']);
        $this->assertArrayNotHasKey('email', $data['profile']);
        $this->assertSame('Kind of Blue', $data['collection']['data'][0]['title']);
        $this->assertSame('Miles Davis', $data['collection']['data'][0]['artist']);
    }

    #[Test]
    public function anonymousCannotReadPrivateProfile(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        SecurityUserFactory::createOne([
            'uuid'     => $uuid,
            'email'    => 'private@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => false,
        ]);

        $client->request('GET', '/api/profiles/'.$uuid->toRfc4122());

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function userCanToggleIsPublicWithoutCurrentPasswordThenProfileBecomesVisible(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = UuidV7::fromString('019c2e97-4f81-75c5-8eca-ec2ff86f7d56');
        $user = SecurityUserFactory::createOne([
            'uuid'     => $uuid,
            'email'    => 'toggle@example.com',
            'password' => 'current-secret',
            'roles'    => ['ROLE_USER'],
            'isPublic' => false,
        ]);

        $jwtManager = $client->getContainer()->get(JWTTokenManagerInterface::class);
        $token      = $jwtManager->create($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        $client->request('PUT', '/api/users/'.$uuid->toRfc4122(), content: json_encode([
            'email'    => 'toggle@example.com',
            'isPublic' => true,
        ]));

        $this->assertResponseStatusCodeSame(204);

        static::ensureKernelShutdown();
        $anonymous = static::createClient();
        $anonymous->request('GET', '/api/profiles/'.$uuid->toRfc4122());

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($anonymous->getResponse()->getContent(), true);
        $this->assertTrue($data['profile']['isPublic']);
    }
}
