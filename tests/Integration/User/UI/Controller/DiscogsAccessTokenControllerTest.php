<?php

namespace App\Tests\Integration\User\UI\Controller;

use App\Tests\JWTAuthenticatedClientTrait;
use App\User\Infra\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DiscogsAccessTokenControllerTest extends WebTestCase
{
    use JWTAuthenticatedClientTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createJWTAuthenticatedClient();
    }

    #[Test]
    public function putStoresEncryptedDiscogsTokenAndReturns204(): void
    {
        $this->client->request(
            'PUT',
            '/api/users/me/discogs-access-token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['accessToken' => 'xYzDiscogsPersonalAccessToken'])
        );

        $this->assertResponseStatusCodeSame(204);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(SecurityUser::class)->findOneBy([]);
        $this->assertNotNull($user->getDiscogsAccessToken());
        $this->assertNotNull($user->getDiscogsAccessTokenNonce());
        $this->assertNotSame('xYzDiscogsPersonalAccessToken', $user->getDiscogsAccessToken());
    }

    #[Test]
    public function putRejectsBlankTokenWith422(): void
    {
        $this->client->request(
            'PUT',
            '/api/users/me/discogs-access-token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['accessToken' => '   '])
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
    }

    #[Test]
    public function putRejectsTokenLongerThanMaxWith422(): void
    {
        $this->client->request(
            'PUT',
            '/api/users/me/discogs-access-token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['accessToken' => str_repeat('a', 257)])
        );

        $this->assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function putRequiresAuthentication(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/users/me/discogs-access-token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['accessToken' => 'xYzDiscogsPersonalAccessToken'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function deleteClearsDiscogsTokenAndReturns204(): void
    {
        $this->client->request(
            'PUT',
            '/api/users/me/discogs-access-token',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['accessToken' => 'xYzDiscogsPersonalAccessToken'])
        );
        $this->assertResponseStatusCodeSame(204);

        $this->client->request('DELETE', '/api/users/me/discogs-access-token');

        $this->assertResponseStatusCodeSame(204);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user = $em->getRepository(SecurityUser::class)->findOneBy([]);
        $this->assertNull($user->getDiscogsAccessToken());
        $this->assertNull($user->getDiscogsAccessTokenNonce());
    }

    #[Test]
    public function deleteRequiresAuthentication(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('DELETE', '/api/users/me/discogs-access-token');

        $this->assertResponseStatusCodeSame(401);
    }
}
