<?php

namespace App\Tests\Integration\User\Infra\Entity;

use App\Tests\Integration\Collection\UI\Controller\ControllerTestCase;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use PHPUnit\Framework\Attributes\Test;

class RefreshTokenTest extends ControllerTestCase
{
    #[Test]
    public function refreshTokenRouteReturnsNewTokenAndRefreshToken(): void
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $container = $client->getContainer();
        $refreshTokenGenerator = $container->get(RefreshTokenGeneratorInterface::class);
        $manager = $container->get(RefreshTokenManagerInterface::class);

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, 2592000);
        $manager->save($refreshToken);

        $client->request(
            'POST',
            '/api/token/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'refresh_token' => $refreshToken->getRefreshToken(),
            ])
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $response);
        $this->assertArrayHasKey('refresh_token', $response);
    }

    #[Test]
    public function refreshTokenRouteIsAccessibleWithoutJwt(): void
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $container = $client->getContainer();
        $refreshTokenGenerator = $container->get(RefreshTokenGeneratorInterface::class);
        $manager = $container->get(RefreshTokenManagerInterface::class);

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, 2592000);
        $manager->save($refreshToken);

        // Create a fresh unauthenticated client
        static::ensureKernelShutdown();
        $unauthClient = static::createClient();

        $unauthClient->request(
            'POST',
            '/api/token/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'refresh_token' => $refreshToken->getRefreshToken(),
            ])
        );

        $response = json_decode($unauthClient->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $response);
    }

    #[Test]
    public function refreshTokenRouteRejectsInvalidToken(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/token/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'refresh_token' => 'invalid-token-that-does-not-exist',
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function refreshTokenRouteRejectsMissingToken(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/token/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function refreshTokenRouteRejectsExpiredToken(): void
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(
            email: 'expired-token-user@test.com'
        );

        $container = $client->getContainer();
        $refreshTokenGenerator = $container->get(RefreshTokenGeneratorInterface::class);
        $manager = $container->get(RefreshTokenManagerInterface::class);

        // Create a token with TTL of 0 (already expired)
        $refreshToken = $refreshTokenGenerator->createForUserWithTtl($user, -1);
        $manager->save($refreshToken);

        $client->request(
            'POST',
            '/api/token/refresh',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'refresh_token' => $refreshToken->getRefreshToken(),
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
