<?php

namespace App\Tests\Integration\Shared\UI\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CorsControllerTest extends WebTestCase
{
    #[Test]
    public function preflightAlbumsEndpointReturnsCorsHeaders(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('OPTIONS', '/api/albums', server: [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization,Content-Type,X-Request-ID',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'http://localhost:3000');
        self::assertResponseHeaderSame('Access-Control-Max-Age', '3600');

        $allowMethods = $client->getResponse()->headers->get('Access-Control-Allow-Methods');
        self::assertNotNull($allowMethods);
        self::assertStringContainsString('GET', $allowMethods);
        self::assertStringContainsString('OPTIONS', $allowMethods);
        self::assertStringContainsString('POST', $allowMethods);
        self::assertStringContainsString('PUT', $allowMethods);
        self::assertStringContainsString('DELETE', $allowMethods);

        $allowHeaders = $client->getResponse()->headers->get('Access-Control-Allow-Headers');
        self::assertNotNull($allowHeaders);
        $allowHeaders = strtolower($allowHeaders);
        self::assertStringContainsString('authorization', $allowHeaders);
        self::assertStringContainsString('content-type', $allowHeaders);
        self::assertStringContainsString('x-request-id', $allowHeaders);
    }

    #[Test]
    public function preflightWithUnauthorizedHeaderReturns400(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('OPTIONS', '/api/albums', server: [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Fake-Header',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function preflightWithUnauthorizedOriginOmitsCorsHeader(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('OPTIONS', '/api/albums', server: [
            'HTTP_ORIGIN'                         => 'http://unauthorizedorigin.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD'  => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Authorization',
        ]);

        self::assertNull($client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }
}
