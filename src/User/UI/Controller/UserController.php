<?php

namespace App\User\UI\Controller;

use App\Shared\UI\Controller\UserAuthorizationTrait;
use App\User\App\Command\CreateUserCommand;
use App\User\App\Command\DeleteUserCommand;
use App\User\App\Command\UpdateUserCommand;
use App\User\App\Query\FindUserQuery;
use App\User\Infra\Security\SecurityUser;
use App\User\UI\RestNormalizer\UserNormalizer;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[Route('/api/users')]
#[OA\Tag(name: 'Users')]
#[Security(name: 'Bearer')]
class UserController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('/me', name: 'user_identity', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Return user identity.', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    public function me(
        UserNormalizer $normalizer,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user = $securityUser->toDomain();

        return $this->json($normalizer->normalize($user), Response::HTTP_OK);
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
            ]
        )
    )]
    #[OA\Response(ref: '#/components/responses/Created', response: 201)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/Conflict', response: 409)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    public function createUser(
        MessageBusInterface $commandBus,
        Request $request,
    ): JsonResponse {
        $uuid = UuidV7::v7();
        $payload = $request->toArray();

        $command = CreateUserCommand::forAdminCreation(
            $uuid,
            $payload['email'],
            $payload['password'],
            $payload['roles'] ?? ['ROLE_USER'],
        );

        $commandBus->dispatch($command);

        return $this->json('', Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('user_find', [
                'uuid' => $command->uuid->toString(),
            ]),
        ]);
    }

    #[Route('/{uuid}', name: 'user_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'User UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(response: 200, description: 'Returns the user', content: new OA\JsonContent(ref: '#/components/schemas/User'))]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    public function findUser(
        Uuid $uuid,
        UserNormalizer $normalizer,
        MessageBusInterface $queryBus,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $query = FindUserQuery::withUuid(
            $uuid,
            $userAuthorization->userUuid,
            $userAuthorization->isAdmin
        );
        $envelope = $queryBus->dispatch($query);

        $user = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            $normalizer->normalize($user),
            Response::HTTP_OK
        );
    }

    #[Route('/{uuid}', name: 'user_update', requirements: ['_format' => 'json'], methods: ['PUT'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'User UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', nullable: true),
                new OA\Property(property: 'password', type: 'string', nullable: true),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
            ]
        )
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    public function updateUser(
        MessageBusInterface $commandBus,
        Request $request,
        Uuid $uuid,
    ): JsonResponse {
        $payload = $request->toArray();

        $userAuthorization = $this->getUserAuthorization();

        $command = UpdateUserCommand::withData(
            $uuid,
            $userAuthorization->userUuid,
            $payload['email'] ?? null,
            $payload['password'] ?? null,
            $payload['currentPassword'] ?? null,
            $payload['roles'] ?? null,
            $userAuthorization->isAdmin
        );

        $commandBus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('/{uuid}', name: 'user_delete', requirements: ['_format' => 'json'], methods: ['DELETE'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'User UUID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/Forbidden', response: 403)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    public function deleteUser(
        MessageBusInterface $commandBus,
        Uuid $uuid,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $command = DeleteUserCommand::withUuid($uuid, $userAuthorization->userUuid, $userAuthorization->isAdmin);

        $commandBus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }
}
