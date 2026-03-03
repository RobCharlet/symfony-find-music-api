<?php

namespace App\Tests\Integration\User\UI\Controller;

use App\Factory\SecurityUserFactory;
use App\Tests\JWTAuthenticatedClientTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;

class UserControllerTest extends WebTestCase
{
    use Factories;
    use JWTAuthenticatedClientTrait;

    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = $this->createJWTAuthenticatedClient(['ROLE_ADMIN']);
    }

    #[Test]
    public function retrieveUser()
    {
        $uuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString($uuid),
            'email'    => 'john@example.com',
            'password' => 'hashed_password',
            'roles'    => ['ROLE_USER'],
        ]);

        $this->client->request('GET', '/api/users/'.$uuid);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame('john@example.com', $data['email']);
        $this->assertArrayHasKey('uuid', $data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('roles', $data);
    }

    #[Test]
    public function unauthenticatedCannotCreateUser()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/users', content: json_encode([
            'email'    => 'user@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('unauthorized', $data['type']);
        $this->assertSame(401, $data['status']);
    }

    #[Test]
    public function authenticatedNonAdminCannotCreateUser()
    {
        $client = $this->createJWTAuthenticatedClient();

        $client->request('POST', '/api/users', content: json_encode([
            'email'    => 'user@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_ADMIN'],
        ]));

        $this->assertResponseStatusCodeSame(403);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function adminCanCreateUser()
    {
        $this->client->request('POST', '/api/users', content: json_encode([
            'email'    => 'admin-created@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($this->client->getResponse()->headers->has('Location'));
    }

    #[Test]
    public function updateUser()
    {
        $uuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString($uuid),
            'email'    => 'old@example.com',
            'password' => 'hashed_password',
            'roles'    => ['ROLE_USER'],
        ]);

        $this->client->request('PUT', '/api/users/'.$uuid, content: json_encode([
            'email'    => 'updated@example.com',
            'password' => 'newpassword',
            'roles'    => ['ROLE_ADMIN'],
        ]));

        $this->assertResponseStatusCodeSame(204);
    }

    #[Test]
    public function deleteUser()
    {
        $uuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString($uuid),
            'email'    => 'todelete@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]);

        $this->client->request('DELETE', '/api/users/'.$uuid);

        $this->assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/users/'.$uuid);

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function retrieveUserNotFound()
    {
        $this->client->request('GET', '/api/users/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function updateUserNotFound()
    {
        $this->client->request('PUT', '/api/users/019c1ec8-961c-7802-90e4-b8163542a2cb', content: json_encode([
            'email'    => 'test@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }

    #[Test]
    public function deleteUserNotFound()
    {
        $this->client->request('DELETE', '/api/users/019c1ec8-961c-7802-90e4-b8163542a2cb');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function updateUserInvalidJsonReturns400()
    {
        $this->client->request('PUT', '/api/users/019c1ec8-961c-7802-90e4-b8163542a2cd', content: '{bad');

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function unauthenticatedCannotRetrieveCurrentUser()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('unauthorized', $data['type']);
        $this->assertSame(401, $data['status']);
        $this->assertSame('JWT Token not found', $data['detail']);
    }

    #[Test]
    public function authenticatedUserCanRetrieveCurrentUser()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = UuidV7::fromString('019c1ec8-961c-7802-90e4-b8163542a2ce');
        $user = SecurityUserFactory::createOne([
            'uuid'     => $uuid,
            'email'    => 'me@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]);

        $jwtManager = $client->getContainer()->get(JWTTokenManagerInterface::class);
        $token      = $jwtManager->create($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        $client->request('GET', '/api/users/me');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($uuid->toRfc4122(), $data['uuid']);
        $this->assertSame('me@example.com', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('roles', $data);
    }

    #[Test]
    public function userLoginSuccessfully()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $uuid = '019c1ec8-961c-7802-90e4-b8163542a2cd';
        SecurityUserFactory::createOne([
            'uuid'     => UuidV7::fromString($uuid),
            'email'    => 'newuser@example.com',
            'password' => 'password',
            'roles'    => ['ROLE_USER'],
        ]);

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email'    => 'newuser@example.com',
                'password' => 'password',
            ])
        );

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    #[Test]
    public function userLoginWithBadCredentialsReturnsUniformError()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        SecurityUserFactory::createOne([
            'email'    => 'legit@example.com',
            'password' => 'correct-password',
            'roles'    => ['ROLE_USER'],
        ]);

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email'    => 'legit@example.com',
                'password' => 'wrong-password',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('unauthorized', $data['type']);
        $this->assertSame('Unauthorized', $data['title']);
        $this->assertSame(401, $data['status']);
        $this->assertSame('Invalid credentials.', $data['detail']);
        $this->assertArrayNotHasKey('code', $data);
        $this->assertArrayNotHasKey('message', $data);
    }

    #[Test]
    public function userLoginBlockAfter3unsuccessfulAttempts()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        static::getContainer()->get('cache.rate_limiter')->clear();
        $email = 'throttle-'.uniqid('', true).'@example.com';
        SecurityUserFactory::createOne([
            'email'    => $email,
            'password' => 'correct-password',
            'roles'    => ['ROLE_USER'],
        ]);

        for ($i = 0; $i < 3; ++$i) {
            $client->request(
                'POST',
                '/api/login_check',
                // json_login only handle JSON requests
                server: ['CONTENT_TYPE' => 'application/json'],
                content: json_encode([
                    'email'    => $email,
                    'password' => 'wrong-password',
                ])
            );

            $this->assertResponseStatusCodeSame(401);
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertSame('unauthorized', $data['type']);
            $this->assertSame('Invalid credentials.', $data['detail']);
            $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        }

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email'    => $email,
                'password' => 'wrong-password',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('unauthorized', $data['type']);
        $this->assertSame('Too many failed login attempts, please try again in 15 minutes.', $data['detail']);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
    }
}
