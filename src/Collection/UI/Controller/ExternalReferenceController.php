<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Command\AddExternalReferenceCommand;
use App\Collection\App\Command\DeleteExternalReferenceCommand;
use App\Collection\App\Command\UpdateExternalReferenceCommand;
use App\Collection\App\Query\FindExternalReferenceQuery;
use App\Collection\App\Query\FindExternalReferencesByAlbumQuery;
use App\Collection\UI\RestNormalizer\ExternalReferenceNormalizer;
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
#[Route('/api/external-references')]
#[OA\Tag(name: 'External References')]
class ExternalReferenceController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('', name: 'external_reference_add', requirements: ['_format' => 'json'], methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['albumUuid', 'platform', 'externalId'],
            properties: [
                new OA\Property(property: 'albumUuid', type: 'string', format: 'uuid'),
                new OA\Property(property: 'platform', type: 'string'),
                new OA\Property(property: 'externalId', type: 'string'),
                new OA\Property(property: 'metadata', type: 'object', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'External reference created', headers: [
        new OA\Header(
            header: 'Location',
            description: 'URL of the created resource',
            schema: new OA\Schema(type: 'string')
        ),
    ])]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 409, description: 'Conflict')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[Security(name: 'Bearer')]
    public function create(MessageBusInterface $commandBus, Request $request): JsonResponse
    {
        $uuid              = UuidV7::v7();
        $payload           = $request->toArray();
        $userAuthorization = $this->getUserAuthorization();

        $command = AddExternalReferenceCommand::withData($uuid, $userAuthorization->userUuid, $userAuthorization->isAdmin, $payload);
        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_CREATED,
            ['Location' => 'api/external-references/'.$command->uuid->toString()]
        );
    }

    #[Route('/{uuid}', name: 'external_reference_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Returns the external reference', content: new OA\JsonContent(
        ref: '#/components/schemas/ExternalReference'
    ))]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    #[Security(name: 'Bearer')]
    public function find(
        ExternalReferenceNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $query    = FindExternalReferenceQuery::withUuid(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin
        );
        $envelope = $queryBus->dispatch($query);

        $externalReference = $envelope->last(HandledStamp::class)->getResult();

        return new JsonResponse(
            $normalizer->normalize($externalReference),
            Response::HTTP_OK
        );
    }

    #[Route(
        '/album/{albumUuid}',
        name: 'external_reference_find_by_album',
        requirements: ['_format' => 'json'],
        methods: ['GET']
    )]
    #[OA\Response(response: 200, description: 'Returns external references of an album', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/ExternalReference')
    ))]
    #[Security(name: 'Bearer')]
    public function findByAlbum(
        ExternalReferenceNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Uuid $albumUuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $query    = FindExternalReferencesByAlbumQuery::withAlbumUuid($albumUuid, $userAuthorization->userUuid, $userAuthorization->isAdmin);
        $envelope = $queryBus->dispatch($query);

        $results = $envelope->last(HandledStamp::class)->getResult();

        $externalReferences = [];

        foreach ($results as $externalReference) {
            $externalReferences[] = $normalizer->normalize($externalReference);
        }

        return new JsonResponse(
            ['data' => $externalReferences],
            Response::HTTP_OK
        );
    }

    #[Route('/{uuid}', name: 'external_reference_update', requirements: ['_format' => 'json'], methods: ['PUT'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'platform', type: 'string'),
                new OA\Property(property: 'externalId', type: 'string'),
                new OA\Property(property: 'metadata', type: 'object', nullable: true),
            ]
        )
    )]
    #[OA\Response(response: 204, description: 'External reference updated')]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    #[Security(name: 'Bearer')]
    public function update(MessageBusInterface $commandBus, Request $request, Uuid $uuid): JsonResponse
    {
        $userAuthorization = $this->getUserAuthorization();

        $payload = $request->toArray();

        $command = UpdateExternalReferenceCommand::withData(
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

    #[Route('/{uuid}', name: 'external_reference_delete', requirements: ['_format' => 'json'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'External reference deleted')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    #[Security(name: 'Bearer')]
    public function delete(MessageBusInterface $commandBus, Uuid $uuid): JsonResponse
    {
        $userAuthorization = $this->getUserAuthorization();

        $command = DeleteExternalReferenceCommand::withUuid(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin
        );
        $commandBus->dispatch($command);

        return new JsonResponse(
            '',
            Response::HTTP_NO_CONTENT
        );
    }
}
