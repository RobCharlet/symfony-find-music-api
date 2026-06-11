<?php

namespace App\User\UI\Controller;

use App\Collection\App\Query\FindPublicCollectionByOwnerQuery;
use App\Collection\UI\RestNormalizer\PublicAlbumNormalizer;
use App\Shared\App\DTO\PaginationDTO;
use App\User\App\Query\FindUserByShareTokenQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/shared')]
#[OA\Tag(name: 'Shared')]
class SharedCollectionController extends AbstractController
{
    private const int MAX_SHARED_COLLECTION_LIMIT = 50;

    #[Route('/{token}', name: 'shared_collection_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'token',
        description: 'Collection share token',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(response: 200, description: 'Returns the shared collection')]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    public function findSharedCollection(
        string $token,
        MessageBusInterface $queryBus,
        PublicAlbumNormalizer $albumNormalizer,
        Request $request,
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_SHARED_COLLECTION_LIMIT, max(1, $request->query->getInt('limit', self::MAX_SHARED_COLLECTION_LIMIT)));

        $owner = $queryBus
            ->dispatch(FindUserByShareTokenQuery::withToken($token))
            ->last(HandledStamp::class)
            ->getResult();

        $paginator = $queryBus
            ->dispatch(FindPublicCollectionByOwnerQuery::withOwnerUuid($owner->getUuid(), $page, $limit))
            ->last(HandledStamp::class)
            ->getResult();

        $albums = [];

        foreach ($paginator as $album) {
            $albums[] = $albumNormalizer->normalize($album);
        }

        return new JsonResponse(
            [
                'data' => $albums,
                'pagination' => PaginationDTO::fromPaginator($paginator),
            ],
            Response::HTTP_OK
        );
    }
}
