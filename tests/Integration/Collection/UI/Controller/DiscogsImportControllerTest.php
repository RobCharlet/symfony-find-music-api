<?php

namespace App\Tests\Integration\Collection\UI\Controller;

use App\Collection\Domain\Album;
use App\Collection\Domain\ExternalReference;
use App\Collection\Domain\PlatformEnum;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DiscogsImportControllerTest extends ControllerTestCase
{
    #[Test]
    public function unauthenticatedCannotImportDiscogsCsv()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/collections/import/discogs');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function importDiscogsCsvWithInvalidFileReturnsBadRequest()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $client->request('POST', '/api/collections/import/discogs');

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid file.', $data['detail']);
    }

    #[Test]
    public function importDiscogsCsvCreatesAlbumAndExternalReference()
    {
        [$client, $user] = $this->createAuthenticatedClientWithUser();

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $albumRepository = $entityManager->getRepository(Album::class);
        $externalReferenceRepository = $entityManager->getRepository(ExternalReference::class);

        $beforeAlbumCount = $albumRepository->count([]);
        $beforeExternalReferenceCount = $externalReferenceRepository->count([]);

        $uploadedFile = $this->createCsvUpload(<<<'CSV'
catalog#,artist,title,label,format,rating,released,release_id,collectionFolder,date_added,collection_media_condition,collection_sleeve_condition,collection_notes
piasr 210 lp,I Am Kloot,Sky At Night,[PIAS] Recordings,"LP, Album",,2010,2596660,Uncategorized,2026-01-19 11:01:49,Near Mint (NM or M-),Near Mint (NM or M-),
CSV);

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $uploadedFile]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['total']);
        $this->assertSame(1, $data['imported']);
        $this->assertSame(0, $data['skipped']);
        $this->assertEmpty($data['errors']);

        $this->assertSame($beforeAlbumCount + 1, $albumRepository->count([]));
        $this->assertSame($beforeExternalReferenceCount + 1, $externalReferenceRepository->count([]));

        $album = $albumRepository->findOneBy([
            'ownerUuid' => $user->getUuid(),
            'title' => 'Sky At Night',
            'artist' => 'I Am Kloot',
        ]);

        $this->assertInstanceOf(Album::class, $album);
        $this->assertSame(2010, $album->getReleaseYear());
        $this->assertSame('LP, Album', $album->getFormat());
        $this->assertSame('[PIAS] Recordings', $album->getLabel());

        $externalReference = $externalReferenceRepository->findOneBy([
            'album' => $album,
            'platform' => PlatformEnum::Discogs,
            'externalId' => '2596660',
        ]);

        $this->assertInstanceOf(ExternalReference::class, $externalReference);
        $this->assertIsArray($externalReference->getMetadata());
    }

    #[Test]
    public function importDiscogsCsvSkipsDuplicateExternalReferences()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $csv = <<<'CSV'
catalog#,artist,title,label,format,rating,released,release_id,collectionFolder,date_added,collection_media_condition,collection_sleeve_condition,collection_notes
VP4178,Culture,Two Sevens Clash,17 North Parade,"LP, Album, RE",,2011,2785131,Uncategorized,2020-03-20 19:48:05,Mint (M),Mint (M),
CSV;

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $this->createCsvUpload($csv)]);
        $this->assertResponseStatusCodeSame(200);

        $firstData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $firstData['imported']);

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $this->createCsvUpload($csv)]);
        $this->assertResponseStatusCodeSame(200);

        $secondData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $secondData['imported']);
        $this->assertSame(1, $secondData['skipped']);
        $this->assertEmpty($secondData['errors']);
    }

    #[Test]
    public function importDiscogsCsvWithCorruptedFileReturnsValidationError()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $csvPath = tempnam(sys_get_temp_dir(), 'discogs_import_');
        file_put_contents($csvPath, random_bytes(256));

        $uploadedFile = new UploadedFile($csvPath, 'corrupted.csv', 'text/csv', null, true);

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $uploadedFile]);

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_error', $data['type']);

        @unlink($csvPath);
    }

    #[Test]
    public function importDiscogsCsvReportsErrorsWithoutCrashing()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $uploadedFile = $this->createCsvUpload(<<<'CSV'
catalog#,artist,title,label,format,rating,released,release_id,collectionFolder,date_added,collection_media_condition,collection_sleeve_condition,collection_notes
29MU032LP,Jay-Jay Johanson,Rorschach Test,29Music,"LP, Album",,2021,17915596,Uncategorized,2026-01-23 04:53:38,Near Mint (NM or M-),Near Mint (NM or M-),
,,,,,,,,,,,,
4AD0020LPE,The National,Sleep Well Beast,4AD,"2xLP, Album, Whi",,2017,10811249,Uncategorized,2024-02-20 04:37:08,,,
CSV);

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $uploadedFile]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $data['total']);
        $this->assertGreaterThanOrEqual(1, $data['imported']);
        $this->assertNotEmpty($data['errors']);
        $this->assertArrayHasKey('line', $data['errors'][0]);
        $this->assertArrayHasKey('message', $data['errors'][0]);
    }

    #[Test]
    public function missingMandatoryCsvColumnsReturnAnArrayWithErrors()
    {
        [$client] = $this->createAuthenticatedClientWithUser();

        $uploadedFile = $this->createCsvUpload(<<<'CSV'
catalog#,label,format,rating,released,release_id,collectionFolder,date_added,collection_media_condition,collection_sleeve_condition,collection_notes
29MU032LP,29Music,"LP, Album",,2021,17915596,Uncategorized,2026-01-23 04:53:38,Near Mint (NM or M-),Near Mint (NM or M-),
,,,,,,,,,,,,
4AD0020LPE,The National,Sleep Well Beast,4AD,"2xLP, Album, Whi",,2017,10811249,Uncategorized,2024-02-20 04:37:08,,,
CSV);

        $client->request('POST', '/api/collections/import/discogs', [], ['file' => $uploadedFile]);

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($data['errors']);
        $this->assertSame('title column is missing.', $data['errors'][0]['message']);
        $this->assertSame('artist column is missing.', $data['errors'][1]['message']);
        $this->assertSame(0, $data['imported']);
    }

    private function createCsvUpload(string $csvContent): UploadedFile
    {
        $csvPath = tempnam(sys_get_temp_dir(), 'discogs_import_');
        file_put_contents($csvPath, $csvContent);

        return new UploadedFile($csvPath, 'discogs.csv', 'text/csv', null, true);
    }
}
