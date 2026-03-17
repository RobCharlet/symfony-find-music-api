<?php

namespace App\User\UI\Controller;

use App\User\App\Command\CreateUserCommand;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\UuidV7;

#[OA\Tag(name: 'Users')]
class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'user_self_register', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User registered',
        headers: [
            new OA\Header(
                header: 'Location',
                description: 'URL of the created user',
                schema: new OA\Schema(type: 'string')
            ),
        ]
    )]
    #[OA\Response(response: 400, description: 'Invalid JSON')]
    #[OA\Response(response: 409, description: 'Conflict')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function register(MessageBusInterface $bus, Request $request): JsonResponse
    {
        $uuid = UuidV7::v7();
        $data = $request->toArray();

        $command = CreateUserCommand::forSelfRegistration(
            $uuid,
            $data['email'],
            $data['password'],
        );

        $bus->dispatch($command);

        return $this->json('', Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('user_find', [
                'uuid' => $command->uuid->toString(),
            ]),
        ]);
    }
}
