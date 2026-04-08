<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Query\FindCollectionQuery;
use App\Collection\UI\RestNormalizer\AlbumNormalizer;
use App\Shared\App\DTO\PaginationDTO;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin')]
class AdminCollectionController extends AbstractController
{
    #[Route('/collections', name: 'admin_collection_list', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(ref: '#/components/parameters/Page')]
    #[OA\Parameter(ref: '#/components/parameters/Limit')]
    #[OA\Response(response: 200, description: 'Returns all collections.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedAlbumResponse'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[Security(name: 'Bearer')]
    public function findCollections(
        AlbumNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Request $request,
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);

        $query = FindCollectionQuery::withPageAndLimit($page, $limit);
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
}
