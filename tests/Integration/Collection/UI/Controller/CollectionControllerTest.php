<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use PHPUnit\Framework\Attributes\Test;

class CollectionControllerTest extends ControllerTestCase
{
    #[Test]
    public function retrieveAlbumsByOwnerUuid()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid());
        $paginator = json_decode($client->getResponse()->getContent(), true);
        $data = $paginator['data'];
        $pagination = $paginator['pagination'];

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame('Animal Magic', $data[0]['title']);
        $this->assertSame('Bonobo', $data[0]['artist']);
        $this->assertSame(2000, $data[0]['releaseYear']);
        $this->assertSame('Vinyle', $data[0]['format']);
        $this->assertSame('Trip Hop', $data[0]['genre']);
        $this->assertSame('Ninja Tune', $data[0]['label']);
        $this->assertSame('https://example.com/cover.jpg', $data[0]['coverUrl']);
        $this->assertSame(1, $pagination['currentPage']);
        $this->assertSame(50, $pagination['maxPerPage']);
        $this->assertSame(1, $pagination['totalItems']);
        $this->assertSame(1, $pagination['totalPages']);
        $this->assertFalse($pagination['hasNextPage']);
        $this->assertFalse($pagination['hasPreviousPage']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidSortedByTitleAsc()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);
        $this->createAlbumOwnedBy($user, ['title' => 'Animal Magic', 'artist' => 'Bonobo']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=title&sort_order=ASC');
        $data = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertResponseIsSuccessful();
        $this->assertSame('Animal Magic', $data[0]['title']);
        $this->assertSame('Mezzanine', $data[1]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidFilteredByGenre()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack', 'genre' => 'Trip Hop']);
        $this->createAlbumOwnedBy($user, ['title' => 'Selected Ambient Works', 'artist' => 'Aphex Twin', 'genre' => 'Electronic']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?genre=Electronic');
        $paginator = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $paginator['pagination']['totalItems']);
        $this->assertSame('Selected Ambient Works', $paginator['data'][0]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithPageAndLimit()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);
        $this->createAlbumOwnedBy($user, ['title' => 'Selected Ambient Works', 'artist' => 'Aphex Twin']);
        $this->createAlbumOwnedBy($user, ['title' => 'Animal Magic', 'artist' => 'Bonobo']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?page=2&limit=1');
        $paginator = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(2, $paginator['pagination']['currentPage']);
        $this->assertSame(1, $paginator['pagination']['maxPerPage']);
        $this->assertSame(3, $paginator['pagination']['totalItems']);
        $this->assertSame(3, $paginator['pagination']['totalPages']);
        $this->assertTrue($paginator['pagination']['hasNextPage']);
        $this->assertTrue($paginator['pagination']['hasPreviousPage']);
        $this->assertCount(1, $paginator['data']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidSortedByTitleDesc()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);
        $this->createAlbumOwnedBy($user, ['title' => 'Animal Magic', 'artist' => 'Bonobo']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=title&sort_order=DESC');
        $data = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertResponseIsSuccessful();
        $this->assertSame('Mezzanine', $data[0]['title']);
        $this->assertSame('Animal Magic', $data[1]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidSortedByTitleWithLowercaseDesc()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);
        $this->createAlbumOwnedBy($user, ['title' => 'Animal Magic', 'artist' => 'Bonobo']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=title&sort_order=desc');
        $data = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertResponseIsSuccessful();
        $this->assertSame('Mezzanine', $data[0]['title']);
        $this->assertSame('Animal Magic', $data[1]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithLowercaseSortOrderIsNormalized()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);
        $this->createAlbumOwnedBy($user, ['title' => 'Animal Magic', 'artist' => 'Bonobo']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=title&sort_order=asc');
        $data = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertResponseIsSuccessful();
        $this->assertSame('Animal Magic', $data[0]['title']);
        $this->assertSame('Mezzanine', $data[1]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithInvalidSortByReturns422()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=invalid_field');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithInvalidSortOrderReturns422()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?sort_by=title&sort_order=INVALID');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidUnauthenticatedReturns401()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/collections/owner/019c2e97-8e0e-776c-bf55-76a2765e369d');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidByDifferentUserReturns403()
    {
        [$ownerClient, $owner] = $this->createAuthenticatedClientWithUser(email: 'owner@test.com');
        $this->createAlbumOwnedBy($owner);

        [$otherClient] = $this->createAuthenticatedClientWithUser(email: 'other@test.com');

        $otherClient->request('GET', '/api/collections/owner/'.$owner->getUuid());

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function retrieveStatsByOwnerUuidReturnsStats()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();
        $this->createAlbumOwnedBy($user);
        $this->createAlbumOwnedBy($user, [
            'title' => 'Black Sands',
            'artist' => 'Bonobo',
            'releaseYear' => 2010,
            'format' => 'CD, Vinyle',
            'genre' => 'Downtempo',
            'label' => 'Ninja Tune',
        ]);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'/stats');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stats', $data);
        $this->assertSame(2, (int) $data['stats']['totalAlbums']);
        $this->assertArrayHasKey('genres', $data['stats']);
        $this->assertArrayHasKey('formats', $data['stats']);
        $this->assertArrayHasKey('releaseYears', $data['stats']);
        $this->assertArrayHasKey('labels', $data['stats']);
    }

    #[Test]
    public function retrieveStatsByOwnerUuidUnauthenticatedReturns401()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/collections/owner/019c2e97-8e0e-776c-bf55-76a2765e369d/stats');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function retrieveStatsByOwnerUuidByDifferentUserReturns403()
    {
        [$ownerClient, $owner] = $this->createAuthenticatedClientWithUser(email: 'statsowner@test.com');
        $this->createAlbumOwnedBy($owner);

        [$otherClient] = $this->createAuthenticatedClientWithUser(email: 'statsother@test.com');

        $otherClient->request('GET', '/api/collections/owner/'.$owner->getUuid().'/stats');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function exportCollectionAsJsonReturnsStreamedJson()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'exportjson@test.com');
        $this->createAlbumOwnedBy($user);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'/export?format=json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getInternalResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertSame('Animal Magic', $data['data'][0]['title']);
        $this->assertSame('Bonobo', $data['data'][0]['artist']);
        $this->assertArrayHasKey('externalReferences', $data['data'][0]);
    }

    #[Test]
    public function exportCollectionAsCsvReturnsStreamedCsv()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'exportcsv@test.com');
        $this->createAlbumOwnedBy($user);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'/export?format=csv');

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('text/csv', $client->getResponse()->headers->get('Content-Type'));

        $content = $client->getInternalResponse()->getContent();
        $lines = explode("\n", trim($content));
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('album_uuid', $lines[0]);
        $this->assertStringContainsString('Animal Magic', $lines[1]);
        $this->assertStringContainsString('Bonobo', $lines[1]);
    }

    #[Test]
    public function exportCollectionDefaultsToJson()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'exportdefault@test.com');
        $this->createAlbumOwnedBy($user);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'/export');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    #[Test]
    public function exportCollectionWithInvalidFormatReturns400()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'exportbad@test.com');

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'/export?format=xml');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('invalid_format', $data['type']);
    }

    #[Test]
    public function exportCollectionUnauthenticatedReturns401()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/collections/owner/019c2e97-8e0e-776c-bf55-76a2765e369d/export');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function exportCollectionByDifferentUserReturns403()
    {
        [$ownerClient, $owner] = $this->createAuthenticatedClientWithUser(email: 'exportowner@test.com');
        $this->createAlbumOwnedBy($owner);

        [$otherClient] = $this->createAuthenticatedClientWithUser(email: 'exportother@test.com');

        $otherClient->request('GET', '/api/collections/owner/'.$owner->getUuid().'/export');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithSearchReturnsMatchingAlbums()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'search@test.com');
        $this->createAlbumOwnedBy($user, ['title' => 'Blue Train', 'artist' => 'John Coltrane', 'label' => 'Blue Note']);
        $this->createAlbumOwnedBy($user, ['title' => 'Kind of Blue', 'artist' => 'Miles Davis', 'label' => 'Columbia']);
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack', 'label' => 'Virgin']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?search=coltrane');
        $paginator = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $paginator['pagination']['totalItems']);
        $this->assertSame('Blue Train', $paginator['data'][0]['title']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithSearchReturnsEmptyWhenNoMatch()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'searchnomatch@test.com');
        $this->createAlbumOwnedBy($user, ['title' => 'Mezzanine', 'artist' => 'Massive Attack']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?search=coltrane');
        $paginator = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $paginator['pagination']['totalItems']);
        $this->assertCount(0, $paginator['data']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithSearchAndSortByReturns422()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'searchandsort@test.com');

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?search=coltrane&sort_by=title');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
        $this->assertSame('sortBy', $data['violations'][0]['field']);
        $this->assertSame('Cannot be combined with search.', $data['violations'][0]['message']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithSearchAndSortOrderReturns422()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'searchandsortorder@test.com');

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?search=coltrane&sort_order=DESC');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('violations', $data);
        $fields = array_column($data['violations'], 'field');
        $this->assertContains('sortOrder', $fields);
        $sortOrderViolation = $data['violations'][array_search('sortOrder', $fields)];
        $this->assertSame('Cannot be combined with search.', $sortOrderViolation['message']);
    }

    #[Test]
    public function retrieveAlbumsByOwnerUuidWithSearchOnLabelReturnsMatchingAlbums()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser(email: 'searchlabel@test.com');
        $this->createAlbumOwnedBy($user, ['title' => 'Blue Train', 'artist' => 'John Coltrane', 'label' => 'Blue Note']);
        $this->createAlbumOwnedBy($user, ['title' => 'Kind of Blue', 'artist' => 'Miles Davis', 'label' => 'Columbia']);

        $client->request('GET', '/api/collections/owner/'.$user->getUuid().'?search=columbia');
        $paginator = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $paginator['pagination']['totalItems']);
        $this->assertSame('Kind of Blue', $paginator['data'][0]['title']);
    }
}
