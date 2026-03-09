<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Command\AddAlbumCommand;
use App\Collection\App\Command\DeleteAlbumCommand;
use App\Collection\App\Command\UpdateAlbumCommand;
use App\Collection\App\Query\FindAlbumQuery;
use App\Collection\App\Query\FindAlbumsByOwnerQuery;
use App\Collection\UI\RestNormalizer\AlbumNormalizer;
use App\Shared\UI\Controller\UserAuthorizationTrait;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[AsController]
#[Route('/api/albums')]
#[OA\Tag(name: 'Albums')]
class AlbumController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('', name: 'album_add', requirements: ['_format' => 'json'], methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'artist'],
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'artist', type: 'string'),
                new OA\Property(property: 'genre', type: 'string', nullable: true),
                new OA\Property(property: 'releaseYear', type: 'integer', nullable: true),
                new OA\Property(property: 'format', type: 'string', nullable: true),
                new OA\Property(property: 'label', type: 'string', nullable: true),
                new OA\Property(property: 'coverUrl', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Album created', headers: [
        new OA\Header(
            header: 'Location',
            description: 'URL of the created album',
            schema: new OA\Schema(type: 'string')
        ),
    ])]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 409, description: 'Conflict')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[Security(name: 'Bearer')]
    public function create(
        MessageBusInterface $commandBus,
        Request $request,
    ): JsonResponse {
        $uuid = UuidV7::v7();

        $userAuthorization = $this->getUserAuthorization();
        $payload = $request->toArray();

        $command = AddAlbumCommand::withData($uuid, $userAuthorization->userUuid, $payload);
        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_CREATED,
            ['Location' => 'api/albums/'.$command->uuid->toString()]
        );
    }

    #[Route('/{uuid}', name: 'album_delete', requirements: ['_format' => 'json'], methods: ['DELETE'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Album UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(response: 204, description: 'Album deleted')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Album not found')]
    #[Security(name: 'Bearer')]
    public function deleteAlbum(
        MessageBusInterface $commandBus,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $command = DeleteAlbumCommand::withUuid($uuid, $userAuthorization->userUuid, $userAuthorization->isAdmin);
        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('/{uuid}', name: 'album_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Album UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(response: 200, description: 'Returns the album', content: new OA\JsonContent(
        ref: '#/components/schemas/Album'
    ))]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Album not found')]
    #[Security(name: 'Bearer')]
    public function findAlbum(
        AlbumNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $query = FindAlbumQuery::withUuid(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin
        );
        $envelope = $queryBus->dispatch($query);

        $album = $envelope->last(HandledStamp::class)->getResult();

        return new JsonResponse(
            $normalizer->normalize($album),
            Response::HTTP_OK
        );
    }

    #[Route('/owner/{uuid}', name: 'album_owner_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'User UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Parameter(ref: '#/components/parameters/Page')]
    #[OA\Parameter(ref: '#/components/parameters/Limit')]
    #[OA\Parameter(ref: '#/components/parameters/SortByAlbum')]
    #[OA\Parameter(ref: '#/components/parameters/SortOrder')]
    #[OA\Parameter(ref: '#/components/parameters/Genre')]
    #[OA\Response(
        response: 200,
        description: 'Returns albums of an owner',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Album')
                ),
                new OA\Property(
                    property: 'pagination',
                    ref: '#/components/schemas/Pagination',
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Security(name: 'Bearer')]
    public function findOwnerAlbums(
        AlbumNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Request $request,
        Uuid $uuid,
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);
        $sortBy = $request->query->getString('sort_by') ?: null;
        $sortOrder = strtoupper($request->query->getString('sort_order')) ?: null;
        $genre = $request->query->getString('genre') ?: null;

        $userAuthorization = $this->getUserAuthorization();

        $query = FindAlbumsByOwnerQuery::withOwnerUuid(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin,
            $page,
            $limit,
            $sortBy,
            $sortOrder,
            $genre
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
                'pagination' => [
                    'currentPage' => $paginator->getCurrentPage(),
                    'maxPerPage' => $paginator->getMaxPerPage(),
                    'totalItems' => $paginator->getTotalItems(),
                    'totalPages' => $paginator->getTotalPages(),
                    'hasNextPage' => $paginator->hasNextPage(),
                    'hasPreviousPage' => $paginator->hasPreviousPage(),
                ],
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/{uuid}', name: 'album_update', requirements: ['_format' => 'json'], methods: ['PUT'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Album UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'artist', type: 'string'),
                new OA\Property(property: 'genre', type: 'string', nullable: true),
                new OA\Property(property: 'releaseYear', type: 'integer', nullable: true),
                new OA\Property(property: 'format', type: 'string', nullable: true),
                new OA\Property(property: 'label', type: 'string', nullable: true),
                new OA\Property(property: 'coverUrl', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 204, description: 'Album updated')]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Album not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[Security(name: 'Bearer')]
    public function updateAlbum(
        MessageBusInterface $commandBus,
        Request $request,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $payload = $request->toArray();

        $command = UpdateAlbumCommand::withData(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin,
            $payload
        );

        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_NO_CONTENT,
        );
    }
}
