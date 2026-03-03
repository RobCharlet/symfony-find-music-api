<?php

namespace App\Tests\Unit\Collection\App\DTO;

use App\Collection\App\DTO\DiscogsAlbumImport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DiscogsAlbumImportTest extends TestCase
{
    #[Test]
    public function discogsAlbumImportConvertsReleasedToInteger()
    {
        $dto = DiscogsAlbumImport::withData([
            'title' => 'Sky At Night',
            'artist' => 'I Am Kloot',
            'released' => '2010',
            'format' => 'LP, Album',
            'label' => '[PIAS] Recordings',
        ]);

        $this->assertSame(2010, $dto->releaseYear);
    }

    #[Test]
    public function discogsAlbumImportSetsReleaseYearToNullWhenInvalid()
    {
        $dtoWithZero = DiscogsAlbumImport::withData(['released' => '0']);
        $this->assertNull($dtoWithZero->releaseYear);

        $dtoWithOutOfRangeYear = DiscogsAlbumImport::withData(['released' => '2200']);
        $this->assertNull($dtoWithOutOfRangeYear->releaseYear);

        $dtoWithNonNumericYear = DiscogsAlbumImport::withData(['released' => 'unknown']);
        $this->assertNull($dtoWithNonNumericYear->releaseYear);
    }
}
