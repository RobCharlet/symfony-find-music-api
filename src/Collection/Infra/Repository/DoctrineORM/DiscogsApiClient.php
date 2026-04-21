<?php

namespace App\Collection\Infra\Repository\DoctrineORM;

use App\Collection\Domain\Repository\DiscogsApiClientInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscogsApiClient implements DiscogsApiClientInterface
{
    public function __construct(
        #[Target('discogs_api.client')]
        private HttpClientInterface $client,
    ) {
    }

    private function request(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $url, $options);
            $data = $response->toArray();
        } catch (ClientExceptionInterface $e) {
            $data = $e->getResponse()->toArray(false);

            $mainErrorMessage = 'Discogs API Error:';

            $error = $data['errors'][0] ?? null;
            if ($error) {
                if (isset($error['status'])) {
                    $mainErrorMessage .= ' '.$error['status'];
                }
                if (isset($error['title'])) {
                    $mainErrorMessage .= ' '.$error['title'];
                }
                if (isset($error['detail'])) {
                    $mainErrorMessage .= ' '.$error['detail'].'""';
                }
                if (isset($error['source']['pointer'])) {
                    $mainErrorMessage .= sprintf(' (at path "%s")', $error['source']['pointer']);
                }
            } else {
                $mainErrorMessage .= $e->getResponse()->getContent(false);
            }

            throw new \Exception($mainErrorMessage, 0, $e);
        }

        return $data;
    }

    public function fetchRelease(string $releaseId, DiscogsAccessToken $token): array
    {
        return $this->request(
            Request::METHOD_GET,
            sprintf('https://api.discogs.com/releases/%s', $releaseId),
            ['headers' => ['Authorization' => 'Discogs token='.$token->value()]],
        );
    }
}
