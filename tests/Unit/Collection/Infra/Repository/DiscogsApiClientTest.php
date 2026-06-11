<?php

namespace App\Tests\Unit\Collection\Infra\Repository;

use App\Collection\Infra\Repository\DoctrineORM\DiscogsApiClient;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

class DiscogsApiClientTest extends TestCase
{
    #[Test]
    public function fetchReleaseReturnsDecodedPayloadAndSendsAuthHeader(): void
    {
        $response = new JsonMockResponse([
            'id' => 249504,
            'title' => 'Nevermind',
            'year' => 1991,
            'genres' => ['Rock'],
            'labels' => [['name' => 'DGC']],
            'images' => [['uri' => 'https://img.discogs.com/nevermind.jpg']],
        ]);

        $client = new DiscogsApiClient(new MockHttpClient($response));
        $token = DiscogsAccessToken::fromString('xYzDiscogsPersonalAccessToken');

        $release = $client->fetchRelease('249504', $token);

        $this->assertSame('Nevermind', $release['title']);
        $this->assertSame(1991, $release['year']);
        $this->assertSame('https://api.discogs.com/releases/249504', $response->getRequestUrl());
        $this->assertContains(
            'Authorization: Discogs token=xYzDiscogsPersonalAccessToken',
            $response->getRequestOptions()['headers'],
        );
    }

    #[Test]
    public function fetchReleaseMapsDiscogsErrorPayloadIntoExceptionMessage(): void
    {
        $response = new JsonMockResponse(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Not Found',
                        'detail' => 'The requested resource was not found.',
                        'source' => ['pointer' => '/releases/999999999'],
                    ],
                ],
            ],
            ['http_code' => 404],
        );

        $client = new DiscogsApiClient(new MockHttpClient($response));
        $token = DiscogsAccessToken::fromString('xYzDiscogsPersonalAccessToken');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Discogs API Error: 404 Not Found The requested resource was not found.""'
            .' (at path "/releases/999999999")',
        );

        $client->fetchRelease('999999999', $token);
    }

    #[Test]
    public function fetchReleaseFallsBackToRawBodyWhenErrorPayloadHasNoErrorsKey(): void
    {
        $response = new JsonMockResponse(
            ['message' => 'You must authenticate to access this resource.'],
            ['http_code' => 401],
        );

        $client = new DiscogsApiClient(new MockHttpClient($response));
        $token = DiscogsAccessToken::fromString('invalidToken');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Discogs API Error:');

        $client->fetchRelease('249504', $token);
    }
}
