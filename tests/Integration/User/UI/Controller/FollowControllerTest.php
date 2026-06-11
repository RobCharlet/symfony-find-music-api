<?php

namespace App\Tests\Integration\User\UI\Controller;

use App\Factory\SecurityUserFactory;
use App\User\Infra\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\UuidV7;

class FollowControllerTest extends WebTestCase
{
    private const string MILES_UUID = '019c2e97-4f81-75c5-8eca-ec2ff86f7d56';
    private const string TRANE_UUID = '019c2e97-5a92-7c11-9f3a-1b2c3d4e5f60';

    #[Test]
    public function followPublicUserIsIdempotentAndReturnsNoContent(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString(self::TRANE_UUID),
            'email'    => 'trane@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => true,
        ]);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/users/me/following');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
    }

    #[Test]
    public function selfFollowReturnsBadRequest(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');

        $client->request('POST', '/api/users/'.self::MILES_UUID.'/follow');

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('cannot_follow_self', $data['type']);
    }

    #[Test]
    public function followPrivateUserReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString(self::TRANE_UUID),
            'email'    => 'trane@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => false,
        ]);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function followMissingUserReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function unfollowIsIdempotentAndReturnsNoContent(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString(self::TRANE_UUID),
            'email'    => 'trane@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => true,
        ]);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $client->request('DELETE', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/users/me/following');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $data['data']);
    }

    #[Test]
    public function followingListReturnsFollowedUsersWithPagination(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString(self::TRANE_UUID),
            'email'    => 'trane@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => true,
        ]);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');

        $client->request('GET', '/api/users/me/following');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(self::TRANE_UUID, $data['data'][0]['uuid']);
        $this->assertTrue($data['data'][0]['isPublic']);
        $this->assertArrayHasKey('followedAt', $data['data'][0]);
        $this->assertArrayNotHasKey('email', $data['data'][0]);
        $this->assertSame(1, $data['pagination']['totalItems']);
        $this->assertSame(1, $data['pagination']['currentPage']);
    }

    #[Test]
    public function followersListReturnsFollowers(): void
    {
        $client = $this->createAuthenticatedClientFor(self::MILES_UUID, 'miles@example.com');
        $trane = SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString(self::TRANE_UUID),
            'email'    => 'trane@example.com',
            'roles'    => ['ROLE_USER'],
            'isPublic' => true,
        ]);

        $client->request('POST', '/api/users/'.self::TRANE_UUID.'/follow');
        $this->assertResponseStatusCodeSame(204);

        $this->authenticate($client, $trane);

        $client->request('GET', '/api/users/me/followers');

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data['data']);
        $this->assertSame(self::MILES_UUID, $data['data'][0]['uuid']);
        $this->assertSame(1, $data['pagination']['totalItems']);
    }

    #[Test]
    public function anonymousCannotUseFollowEndpoints(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/users/me/following');

        $this->assertResponseStatusCodeSame(401);
    }

    private function createAuthenticatedClientFor(string $uuid, string $email): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $user = SecurityUserFactory::createOne([
            'uuid'  => UuidV7::fromString($uuid),
            'email' => $email,
            'roles' => ['ROLE_USER'],
        ]);

        $this->authenticate($client, $user);

        return $client;
    }

    private function authenticate(KernelBrowser $client, SecurityUser $user): void
    {
        $jwtManager = $client->getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);

        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));
    }
}
