<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Query\FindCollectionQuery;
use App\Collection\UI\RestNormalizer\AlbumNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminCollectionController extends AbstractController
{
    #[Route('/collections', name: 'admin_collection_list', requirements: ['_format' => 'json'], methods: ['GET'])]
    public function findCollections(
        AlbumNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Request $request,
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);

        $query    = FindCollectionQuery::withPageAndLimit($page, $limit);
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
}
