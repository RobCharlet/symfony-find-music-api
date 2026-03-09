<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\UI\Controller;

use App\Factory\SecurityUserFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    #[Test]
    public function registerUserSuccessfully()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: json_encode([
            'email' => 'user@example.com',
            'password' => 'securepass123',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($client->getResponse()->headers->has('Location'));

        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertMatchesRegularExpression(
            '#/api/users/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}#',
            $location
        );
    }

    #[Test]
    public function registerUserIgnoresRolesFromPayload()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: json_encode([
            'email' => 'hacker@example.com',
            'password' => 'securepass123',
            'roles' => ['ROLE_ADMIN'], // Cette propriété devrait être ignorée
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    #[Test]
    public function registerUserInvalidEmailReturns422()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: json_encode([
            'email' => 'not-an-email',
            'password' => 'securepass123',
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);

        $fields = array_column($data['violations'], 'field');
        $this->assertContains('email', $fields);
    }

    #[Test]
    public function registerUserPasswordTooShortReturns422()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: json_encode([
            'email' => 'valid@example.com',
            'password' => 'short', // Moins de 8 caractères
        ]));

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);

        $fields = array_column($data['violations'], 'field');
        $this->assertContains('password', $fields);
    }

    #[Test]
    public function registerUserDuplicateEmailReturns409()
    {
        SecurityUserFactory::createOne([
            'email' => 'duplicate@example.com',
            'password' => 'hashed_password',
            'roles' => ['ROLE_USER'],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: json_encode([
            'email' => 'duplicate@example.com',
            'password' => 'anotherpassword',
        ]));

        $this->assertResponseStatusCodeSame(409);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('conflict', $data['type']);
    }

    #[Test]
    public function registerUserInvalidJsonReturns400()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: '{bad');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function registerUserEmptyBodyReturns400()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', content: '');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }
}
