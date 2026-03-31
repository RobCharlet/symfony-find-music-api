<?php

namespace App\Collection\UI\Exporter;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvCollectionExporter
{
    public function streamCollectionAsCsv(\Generator $collection): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($collection) {
                $headers = [
                    'album_uuid',
                    'title',
                    'artist',
                    'release_year',
                    'format',
                    'genre',
                    'label',
                    'cover_url',
                    'created_at',
                    'updated_at',
                    'is_favorite',
                    'external_references',
                ];

                $out = fopen('php://output', 'w');
                fputcsv($out, $headers);

                foreach ($collection as $album) {

                    // Decode metadata before re-encoding as JSON.
                    $externalReferences = $album['externalReferences'];

                    $externalReferences = array_map(function ($externalReference) {
                        $externalReference['metadata'] = json_decode($externalReference['metadata'], true);

                        return $externalReference;
                    }, $externalReferences);

                    fputcsv($out, [
                        $album['albumUuid'],
                        $album['title'],
                        $album['artist'],
                        $album['releaseYear'],
                        $album['format'],
                        $album['genre'],
                        $album['label'],
                        $album['coverUrl'],
                        $album['createdAt'],
                        $album['updatedAt'],
                        $album['isFavorite'],
                        json_encode($externalReferences),
                    ]);
                }

                fclose($out);
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="collection.csv"',
            ]
        );
    }
}
