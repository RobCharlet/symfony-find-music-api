<?php

declare(strict_types=1);

namespace App\User\UI\Controller;

use App\Shared\UI\Controller\UserAuthorizationTrait;
use App\User\App\Command\ClearDiscogsAccessTokenCommand;
use App\User\App\Command\CreateDiscogsAccessTokenCommand;
use App\User\UI\DTO\DiscogsAccessTokenPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/users/me/discogs-access-token')]
class DiscogsAccessTokenController extends AbstractController
{
    use UserAuthorizationTrait;

    #[Route('', name: 'put_discogs_access_token', methods: ['PUT'])]
    public function putDiscogsAccessToken(
        MessageBusInterface $commandBus,
        #[MapRequestPayload] DiscogsAccessTokenPayload $payload,
    ): JsonResponse {

        $userAuthorization = $this->getUserAuthorization();

        $command = CreateDiscogsAccessTokenCommand::withAccessToken(
            $userAuthorization->userUuid,
            $payload->accessToken,
            $userAuthorization->isAdmin
        );

        $commandBus->dispatch($command);

        return $this->json(
            '',
            Response::HTTP_NO_CONTENT
        );
    }

    #[Route('', name: 'clear_discogs_access_token', methods: ['DELETE'])]
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
