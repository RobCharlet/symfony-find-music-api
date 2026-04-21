<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Command\AddAlbumCommand;
use App\Collection\App\Command\DeleteAlbumCommand;
use App\Collection\App\Command\EnrichAlbumFromDiscogsCommand;
use App\Collection\App\Command\UpdateAlbumCommand;
use App\Collection\App\Query\FindAlbumQuery;
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

    #[Route('', name: 'album_create', requirements: ['_format' => 'json'], methods: ['POST'])]
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
                new OA\Property(property: 'isFavorite', type: 'boolean', nullable: true),
            ]
        )
    )]
    #[OA\Response(ref: '#/components/responses/Created', response: 201)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Conflict', response: 409)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
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
            ['Location' => $this->generateUrl('album_find', ['uuid' => $command->uuid->toString()])]
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
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    #[Security(name: 'Bearer')]
    public function delete(
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
    #[OA\Response(response: 200, description: 'Returns the album', content: new OA\JsonContent(ref: '#/components/schemas/Album'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    #[Security(name: 'Bearer')]
    public function find(
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
                new OA\Property(property: 'isFavorite', type: 'boolean'),
                new OA\Property(property: 'genre', type: 'string', nullable: true),
                new OA\Property(property: 'releaseYear', type: 'integer', nullable: true),
                new OA\Property(property: 'format', type: 'string', nullable: true),
                new OA\Property(property: 'label', type: 'string', nullable: true),
                new OA\Property(property: 'coverUrl', type: 'string', nullable: true),
                new OA\Property(property: 'rating', type: 'integer', nullable: true),
                new OA\Property(property: 'personalNote', type: 'string', nullable: true),
            ]
        )
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    #[Security(name: 'Bearer')]
    public function update(
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

    #[Route('/{uuid}/enrich', name: 'album_enrich', requirements: ['_format' => 'json'], methods: ['POST'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Album UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    #[Security(name: 'Bearer')]
    public function enrich(
        MessageBusInterface $commandBus,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $command = EnrichAlbumFromDiscogsCommand::withAlbumUuid($uuid, $userAuthorization->userUuid);
        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_NO_CONTENT
        );

    }
}
