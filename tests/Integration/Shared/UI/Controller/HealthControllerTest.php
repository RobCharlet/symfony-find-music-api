<?php

namespace App\Tests\Integration\Shared\UI\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    #[Test]
    public function healthCheckReturnStatusOk()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $responseContent = $client->getResponse()->getContent();
        self::assertSame('{"status":"ok"}', $responseContent);
    }
}
