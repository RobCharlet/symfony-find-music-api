<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Query\FindAlbumsByOwnerWithPaginationQuery;
use App\Collection\App\Query\FindCollectionByOwnerQuery;
use App\Collection\App\Query\GetStatsByOwnerQuery;
use App\Collection\UI\Exception\InvalidExportFormatException;
use App\Collection\UI\Exporter\CsvCollectionExporter;
use App\Collection\UI\RestNormalizer\AlbumNormalizer;
use App\Shared\App\DTO\PaginationDTO;
use App\Shared\UI\Controller\UserAuthorizationTrait;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/collections')]
class CollectionController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('/owner/{ownerUuid}/export', name: 'collection_owner_export', methods: ['GET'])]
    #[OA\Parameter(
        name: 'ownerUuid',
        description: 'Owner UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Parameter(
        name: 'format',
        description: 'Export format',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'json', enum: ['json', 'csv'])
    )]
    #[OA\Response(response: 200, description: 'Returns the full collection as JSON stream or CSV file')]
    #[OA\Response(response: 400, description: 'Invalid export format')]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[Security(name: 'Bearer')]
    public function export(
        CsvCollectionExporter $exporter,
        MessageBusInterface $queryBus,
        Request $request,
        Uuid $ownerUuid,
    ): Response|StreamedResponse|StreamedJsonResponse {
        $userAuthorization = $this->getUserAuthorization();
        $format = $request->query->getString('format') ?: 'json';

        $query = FindCollectionByOwnerQuery::withOwnerUuid(
            $ownerUuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin
        );

        $envelope = $queryBus->dispatch($query);
        $collection = $envelope->last(HandledStamp::class)->getResult();

        return match ($format) {
            'json' => new StreamedJsonResponse([
                'data' => $collection,
            ]),
            'csv' => $exporter->streamCollectionAsCsv($collection),
            default => throw new InvalidExportFormatException(),
        };
    }

    #[Route('/owner/{ownerUuid}', name: 'collection_owner_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'ownerUuid',
        description: 'Owner UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Parameter(ref: '#/components/parameters/Page')]
    #[OA\Parameter(ref: '#/components/parameters/Limit')]
    #[OA\Parameter(ref: '#/components/parameters/SortByAlbum')]
    #[OA\Parameter(ref: '#/components/parameters/SortOrder')]
    #[OA\Parameter(ref: '#/components/parameters/isFavorite')]
    #[OA\Parameter(ref: '#/components/parameters/Genre')]
    #[OA\Parameter(ref: '#/components/parameters/Search')]
    #[OA\Parameter(ref: '#/components/parameters/Artist')]
    #[OA\Parameter(ref: '#/components/parameters/Format')]
    #[OA\Parameter(ref: '#/components/parameters/Label')]
    #[OA\Parameter(ref: '#/components/parameters/YearFrom')]
    #[OA\Parameter(ref: '#/components/parameters/YearTo')]
    #[OA\Response(response: 200, description: 'Returns albums of an owner', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAlbumResponse'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[Security(name: 'Bearer')]
    public function findByOwner(
        AlbumNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Request $request,
        Uuid $ownerUuid,
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 50));
        $sortBy = $request->query->getString('sort_by') ?: null;
        $sortOrder = strtoupper($request->query->getString('sort_order')) ?: null;
        $isFavorite = '' !== $request->query->getString('isFavorite') ?
            $request->query->getBoolean('isFavorite') :
            null
        ;
        $genre = $request->query->getString('genre') ?: null;
        $search = $request->query->getString('search') ?: null;
        $artist = $request->query->getString('artist') ?: null;
        $format = $request->query->getString('format') ?: null;
        $label = $request->query->getString('label') ?: null;
        $yearFrom = (int) $request->query->get('year_from') ?: null;
        $yearTo = (int) $request->query->get('year_to') ?: null;

        $userAuthorization = $this->getUserAuthorization();

        $query = FindAlbumsByOwnerWithPaginationQuery::withOwnerUuid(
            $ownerUuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $isFavorite,
            $genre,
            $search,
            $artist,
            $format,
            $label,
            $yearFrom,
            $yearTo,
        );

        $envelope = $queryBus->dispatch($query);
        $paginator = $envelope->last(HandledStamp::class)->getResult();

        $albums = [];

        foreach ($paginator as $album) {
            $albums[] = $normalizer->normalize($album);
        }

        return new JsonResponse(
            [
                'data' => $albums,
                'pagination' => PaginationDTO::fromPaginator($paginator),
            ],
            Response::HTTP_OK
        );
    }

    #[Route(
        '/owner/{ownerUuid}/stats',
        name: 'collection_owner_stats',
        requirements: ['_format' => 'json'],
        methods: ['GET']
    )]
    #[OA\Parameter(
        name: 'ownerUuid',
        description: 'Owner UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(response: 200, description: 'Returns collection statistics of an owner', content: new OA\JsonContent(ref: '#/components/schemas/StatisticsResponse'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    public function stats(
        MessageBusInterface $queryBus,
        Uuid $ownerUuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $query = GetStatsByOwnerQuery::withOwnerUuid(
            $ownerUuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin,
        );
        $envelope = $queryBus->dispatch($query);

        $stats = $envelope->last(HandledStamp::class)->getResult();

        return new JsonResponse(
            ['stats' => $stats],
            Response::HTTP_OK
        );
    }
}
