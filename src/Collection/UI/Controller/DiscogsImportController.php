<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Command\ImportCsvCommand;
use App\Shared\UI\Controller\UserAuthorizationTrait;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

class DiscogsImportController extends AbstractController
{
    use UserAuthorizationTrait;

    #[OA\Tag(name: 'Albums')]
    #[Route('/api/collections/import/discogs', name: 'collection_import_discogs', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(
                        property: 'file',
                        description: 'Discogs CSV file',
                        type: 'string',
                        format: 'binary'
                    ),
                ],
                type: 'object'
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Import report',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 3),
                new OA\Property(property: 'imported', type: 'integer', example: 2),
                new OA\Property(property: 'skipped', type: 'integer', example: 1),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'line', type: 'integer', example: 12, nullable: true),
                            new OA\Property(property: 'message', type: 'string', example: 'Missing required value(s): title.'),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request (invalid file or import failure report)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'detail', type: 'string', example: 'Invalid file.', nullable: true),
                new OA\Property(property: 'total', type: 'integer', nullable: true),
                new OA\Property(property: 'imported', type: 'integer', nullable: true),
                new OA\Property(property: 'skipped', type: 'integer', nullable: true),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'line', type: 'integer', nullable: true),
                            new OA\Property(property: 'message', type: 'string'),
                        ],
                        type: 'object'
                    ),
                    nullable: true
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    #[Security(name: 'Bearer')]
    public function import(
        MessageBusInterface $messageBus,
        Request $request,
    ): JsonResponse {

        $userAuthorization = $this->getUserAuthorization();
        $file              = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['detail' => 'Invalid file.'], 400);
        }

        $command = ImportCsvCommand::withFilePath(
            $file->getPathname(),
            $userAuthorization->userUuid
        );

        $envelope = $messageBus->dispatch($command);
        $results = $envelope->last(HandledStamp::class)->getResult();

        $statusCode = ($results['imported'] > 0 || $results['skipped'] > 0)
            ? Response::HTTP_OK
            : Response::HTTP_BAD_REQUEST;

        return $this->json($results, $statusCode);
    }
}
