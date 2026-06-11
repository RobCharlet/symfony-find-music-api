<?php

namespace App\User\UI\Controller;

use App\Collection\App\Query\FindPublicCollectionByOwnerQuery;
use App\Collection\UI\RestNormalizer\PublicAlbumNormalizer;
use App\Shared\App\DTO\PaginationDTO;
use App\User\App\Query\FindPublicProfileQuery;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/profiles')]
#[OA\Tag(name: 'Profiles')]
class ProfileController extends AbstractController
{
    private const int MAX_PUBLIC_COLLECTION_LIMIT = 50;

    #[Route('/{uuid}', name: 'profile_public_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'User UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(response: 200, description: 'Returns the public profile with its collection')]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    public function findPublicProfile(
        Uuid $uuid,
        MessageBusInterface $queryBus,
        PublicAlbumNormalizer $albumNormalizer,
        Request $request,
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_PUBLIC_COLLECTION_LIMIT, max(1, $request->query->getInt('limit', self::MAX_PUBLIC_COLLECTION_LIMIT)));

        $profile = $queryBus
            ->dispatch(FindPublicProfileQuery::withUuid($uuid))
            ->last(HandledStamp::class)
            ->getResult();

        $paginator = $queryBus
            ->dispatch(FindPublicCollectionByOwnerQuery::withOwnerUuid($uuid, $page, $limit))
            ->last(HandledStamp::class)
            ->getResult();

        $albums = [];

        foreach ($paginator as $album) {
            $albums[] = $albumNormalizer->normalize($album);
        }

        return new JsonResponse(
            [
                'profile' => [
                    'uuid' => $profile->getUuid(),
                    'isPublic' => $profile->isPublic(),
                ],
                'collection' => [
                    'data' => $albums,
                    'pagination' => PaginationDTO::fromPaginator($paginator),
                ],
            ],
            Response::HTTP_OK
        );
    }
}
