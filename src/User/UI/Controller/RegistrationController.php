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
    #[OA\Response(ref: '#/components/responses/Created', response: 201)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Conflict', response: 409)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    public function register(MessageBusInterface $commandBus, Request $request): JsonResponse
    {
        $uuid = UuidV7::v7();
        $payload = $request->toArray();

        $command = CreateUserCommand::forSelfRegistration(
            $uuid,
            $payload['email'],
            $payload['password'],
        );

        $commandBus->dispatch($command);

        return $this->json('', Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('user_find', [
                'uuid' => $command->uuid->toString(),
            ]),
        ]);
    }
}
