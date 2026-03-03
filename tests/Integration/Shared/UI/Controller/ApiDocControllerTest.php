<?php

namespace App\Tests\Integration\Shared\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocControllerTest extends WebTestCase
{
    public function testGetApiDocJsonReturns200(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/doc.json');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }
}
