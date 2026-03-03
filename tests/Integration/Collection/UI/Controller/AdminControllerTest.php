<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use PHPUnit\Framework\Attributes\Test;

class AdminControllerTest extends ControllerTestCase
{
    #[Test]
    public function adminCanListCollectionsWithPagination()
    {
        [$client, $adminUser] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $this->createAlbumOwnedBy($adminUser, ['title' => 'Alpha']);
        $this->createAlbumOwnedBy($adminUser, ['title' => 'Beta']);
        $this->createAlbumOwnedBy($adminUser, ['title' => 'Gamma']);

        $client->request('GET', '/api/admin/collections?page=1&limit=2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(2, $data['data']);
        $this->assertSame(1, $data['pagination']['currentPage']);
        $this->assertSame(2, $data['pagination']['maxPerPage']);
        $this->assertGreaterThanOrEqual(3, $data['pagination']['totalItems']);
        $this->assertTrue($data['pagination']['hasNextPage']);
    }

    #[Test]
    public function adminCanListCollectionsWithoutPaginationParams()
    {
        [$client, $adminUser] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);
        $this->createAlbumOwnedBy($adminUser, ['title' => 'No Params']);

        $client->request('GET', '/api/admin/collections');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['pagination']['currentPage']);
        $this->assertSame(50, $data['pagination']['maxPerPage']);
    }

    #[Test]
    public function adminCannotListCollectionsWithInvalidPage()
    {
        [$client] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $client->request('GET', '/api/admin/collections?page=0&limit=50');

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $fields = array_column($data['violations'], 'field');
        $this->assertContains('page', $fields);
    }

    #[Test]
    public function adminCannotListCollectionsWithInvalidLimit()
    {
        [$client] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $client->request('GET', '/api/admin/collections?page=1&limit=101');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $fields = array_column($data['violations'], 'field');
        $this->assertContains('limit', $fields);
    }

    #[Test]
    public function authenticatedNonAdminCannotListCollections()
    {
        [$client] = $this->createAuthenticatedClientWithUser(['ROLE_USER']);

        $client->request('GET', '/api/admin/collections?page=1&limit=50');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function unauthenticatedCannotListCollections()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/admin/collections?page=1&limit=50');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function adminCanListExternalReferenceWithPagination()
    {
        [$client, $adminUser] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $this->createExternalReferencesWithAlbumOwnedByAndReturnExternalReferences($adminUser, []);

        $client->request('GET', '/api/admin/external-references?page=1&limit=2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(1, $data['data']);
        $this->assertSame(1, $data['pagination']['currentPage']);
        $this->assertSame(2, $data['pagination']['maxPerPage']);
        $this->assertGreaterThanOrEqual(1, $data['pagination']['totalItems']);
        $this->assertFalse($data['pagination']['hasNextPage']);
    }

    #[Test]
    public function adminCanListExternalReferenceWithoutPaginationParams()
    {
        [$client, $adminUser] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);
        $this->createExternalReferencesWithAlbumOwnedByAndReturnExternalReferences($adminUser, []);

        $client->request('GET', '/api/admin/external-references');

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['pagination']['currentPage']);
        $this->assertSame(50, $data['pagination']['maxPerPage']);
    }

    #[Test]
    public function adminCannotListExternalReferenceWithInvalidPage()
    {
        [$client] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $client->request('GET', '/api/admin/external-references?page=0&limit=50');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $fields = array_column($data['violations'], 'field');
        $this->assertContains('page', $fields);
    }

    #[Test]
    public function adminCannotListExternalReferenceWithInvalidLimit()
    {
        [$client] = $this->createAuthenticatedClientWithUser(['ROLE_ADMIN']);

        $client->request('GET', '/api/admin/external-references?page=1&limit=101');

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $fields = array_column($data['violations'], 'field');
        $this->assertContains('limit', $fields);
    }

    #[Test]
    public function authenticatedNonAdminCannotListExternalReferenceWithPagination()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $client->request('GET', '/api/admin/external-references?page=1&limit=50');

        $this->assertResponseStatusCodeSame(403);
    }

    #[Test]
    public function unauthenticatedCannotListExternalReferenceWithPagination()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/api/admin/external-references?page=1&limit=50');

        $this->assertResponseStatusCodeSame(401);
    }
}
