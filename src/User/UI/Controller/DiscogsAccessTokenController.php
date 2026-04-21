<?php

namespace App\User\UI\Controller;

use App\Shared\UI\Controller\UserAuthorizationTrait;
use App\User\App\Command\ClearDiscogsAccessTokenCommand;
use App\User\App\Command\CreateDiscogsAccessTokenCommand;
use App\User\UI\DTO\DiscogsAccessTokenPayload;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/users/me/discogs-access-token')]
#[OA\Tag(name: 'Discogs')]
class DiscogsAccessTokenController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('', name: 'put_discogs_access_token', methods: ['PUT'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['accessToken'],
            properties: [
                new OA\Property(property: 'accessToken', type: 'string'),
            ]
        )
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/InvalidJson', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/ValidationError', response: 422)]
    #[Security(name: 'Bearer')]
    public function putDiscogsAccessToken(
        MessageBusInterface $commandBus,
        #[MapRequestPayload]
        #[\SensitiveParameter]
        DiscogsAccessTokenPayload $payload,
    ): JsonResponse {

        $userAuthorization = $this->getUserAuthorization();

        $command = CreateDiscogsAccessTokenCommand::withAccessToken(
            $userAuthorization->userUuid,
            $payload->accessToken,
        );

        $commandBus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('', name: 'clear_discogs_access_token', methods: ['DELETE'])]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[Security(name: 'Bearer')]
    public function clearDiscogsAccessToken(
        MessageBusInterface $commandBus,
    ): JsonResponse {
        $command = ClearDiscogsAccessTokenCommand::withUserUuid($this->getUserAuthorization()->userUuid);
        $commandBus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }
}
