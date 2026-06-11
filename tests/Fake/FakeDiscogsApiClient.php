<?php

namespace App\Tests\Fake;

use App\Collection\Domain\Repository\DiscogsApiClientInterface;
use App\User\Domain\ValueObject\DiscogsAccessToken;

/**
 * In-memory Discogs client used in the test environment to keep the
 * integration suite hermetic (no real call to api.discogs.com).
 */
class FakeDiscogsApiClient implements DiscogsApiClientInterface
{
    public function fetchRelease(string $releaseId, DiscogsAccessToken $token): array
    {
        return [
            'id' => (int) $releaseId,
            'title' => 'The Dark Side of the Moon',
            'year' => 1973,
            'genres' => ['Rock'],
            'styles' => ['Prog Rock', 'Psychedelic Rock'],
            'labels' => [
                ['name' => 'Harvest', 'catno' => 'SHVL 804'],
            ],
            'images' => [
                ['uri' => 'https://img.discogs.com/the-dark-side-of-the-moon-cover.jpg'],
            ],
        ];
    }
}
