<?php

namespace App\User\UI\Controller;

use App\Collection\Domain\PaginatorInterface;
use App\Shared\App\DTO\PaginationDTO;
use App\Shared\UI\Controller\UserAuthorizationTrait;
use App\User\App\Command\FollowUserCommand;
use App\User\App\Command\UnfollowUserCommand;
use App\User\App\Query\FindFollowersQuery;
use App\User\App\Query\FindFollowingQuery;
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

#[Route('/api/users')]
#[OA\Tag(name: 'Follows')]
#[Security(name: 'Bearer')]
class FollowController extends AbstractController
{
    use UserAuthorizationTrait;

    private const int MAX_FOLLOW_LIST_LIMIT = 50;

    #[Route('/{uuid}/follow', name: 'user_follow', methods: ['POST'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'UUID of the user to follow',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/BadRequest', response: 400)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    #[OA\Response(ref: '#/components/responses/NotFound', response: 404)]
    public function follow(
        Uuid $uuid,
        MessageBusInterface $commandBus,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $command = FollowUserCommand::forUsers($userAuthorization->userUuid, $uuid);

        $commandBus->dispatch($command);

        return $this->json('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/{uuid}/follow', name: 'user_unfollow', methods: ['DELETE'])]
    #[OA\Parameter(
        name: 'uuid',
        description: 'UUID of the user to unfollow',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(ref: '#/components/responses/NoContent', response: 204)]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    public function unfollow(
        Uuid $uuid,
        MessageBusInterface $commandBus,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();

        $command = UnfollowUserCommand::forUsers($userAuthorization->userUuid, $uuid);

        $commandBus->dispatch($command);

        return $this->json('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/me/following', name: 'user_following', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Returns the paginated list of followed users')]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    public function following(
        MessageBusInterface $queryBus,
        Request $request,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();
        [$page, $limit] = $this->getPagination($request);

        $paginator = $queryBus
            ->dispatch(FindFollowingQuery::forUser($userAuthorization->userUuid, $page, $limit))
            ->last(HandledStamp::class)
            ->getResult();

        return $this->paginatedFollowResponse($paginator);
    }

    #[Route('/me/followers', name: 'user_followers', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Returns the paginated list of followers')]
    #[OA\Response(ref: '#/components/responses/Unauthorized', response: 401)]
    public function followers(
        MessageBusInterface $queryBus,
        Request $request,
    ): JsonResponse {
        $userAuthorization = $this->getUserAuthorization();
        [$page, $limit] = $this->getPagination($request);

        $paginator = $queryBus
            ->dispatch(FindFollowersQuery::forUser($userAuthorization->userUuid, $page, $limit))
            ->last(HandledStamp::class)
            ->getResult();

        return $this->paginatedFollowResponse($paginator);
    }

    /**
     * @return array{int, int}
     */
    private function getPagination(Request $request): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(self::MAX_FOLLOW_LIST_LIMIT, max(1, $request->query->getInt('limit', self::MAX_FOLLOW_LIST_LIMIT)));

        return [$page, $limit];
    }

    private function paginatedFollowResponse(PaginatorInterface $paginator): JsonResponse
    {
        $users = [];

        foreach ($paginator as $row) {
            $users[] = [
                'uuid' => $row['uuid']->toRfc4122(),
                'isPublic' => $row['isPublic'],
                'followedAt' => $row['followedAt']->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse(
            [
                'data' => $users,
                'pagination' => PaginationDTO::fromPaginator($paginator),
            ],
            Response::HTTP_OK
        );
    }
}
