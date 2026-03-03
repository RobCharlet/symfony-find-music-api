<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Command\ImportCsvCommand;
use App\Shared\UI\Controller\UserAuthorizationTrait;
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

    #[Route('/api/collections/import/discogs', name: 'api_collections_import_discogs', methods: ['POST'])]
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
