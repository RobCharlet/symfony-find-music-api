<?php

namespace App\User\UI\Controller;

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
    #[Route('/me', name: 'user_identity', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return user identity.',
        content: new OA\JsonContent(
            ref: '#/components/schemas/User',
        ),
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    public function me(
        UserNormalizer $normalizer,
    ): JsonResponse {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->getUser();
        $user         = $securityUser->toDomain();

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
    #[OA\Response(
        response: 201,
        description: 'Create a new user.',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'Return the user API location.',
                schema: new OA\Schema(type: 'string')
            ),
        ]
    )]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 409, description: 'Conflict')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function createUser(
        MessageBusInterface $bus,
        Request $request,
    ): JsonResponse {
        $uuid = UuidV7::v7();
        $data = $request->toArray();

        $command = CreateUserCommand::forAdminCreation(
            $uuid,
            $data['email'],
            $data['password'],
            $data['roles'] ?? ['ROLE_USER'],
        );

        $bus->dispatch($command);

        return $this->json('', Response::HTTP_CREATED, [
            'Location' => '/api/users/'.$command->uuid->toString(),
        ]);
    }

    #[Route('/{uuid}', name: 'user_find', requirements: ['_format' => 'json'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Returns the user', content: new OA\JsonContent(
        ref: '#/components/schemas/User'
    ))]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function findUser(
        string $uuid,
        UserNormalizer $normalizer,
        MessageBusInterface $bus,
    ): JsonResponse {
        $query = FindUserQuery::withUuid(UuidV7::fromString($uuid));
        $envelope = $bus->dispatch($query);

        $user = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            $normalizer->normalize($user),
            Response::HTTP_OK
        );
    }

    #[Route('/{uuid}', name: 'user_update', requirements: ['_format' => 'json'], methods: ['PUT'])]
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
    #[OA\Response(response: 204, description: 'User updated')]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function updateUser(
        MessageBusInterface $bus,
        Request $request,
        Uuid $uuid,
    ): JsonResponse {
        $data = $request->toArray();

        $command = UpdateUserCommand::withData(
            $uuid,
            $data['email'] ?? null,
            $data['password'] ?? null,
            $data['roles']
        );

        $bus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('/{uuid}', name: 'user_delete', requirements: ['_format' => 'json'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'User deleted')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function deleteUser(
        MessageBusInterface $bus,
        Uuid $uuid,
    ): JsonResponse {
        $command = DeleteUserCommand::withUuid($uuid);

        $bus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }
}
