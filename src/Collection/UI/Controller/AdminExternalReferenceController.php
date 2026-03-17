<?php

namespace App\Collection\UI\Controller;

use App\Collection\App\Query\FindExternalReferencesQuery;
use App\Collection\Domain\PaginatorInterface;
use App\Collection\UI\RestNormalizer\ExternalReferenceNormalizer;
use App\Shared\App\DTO\PaginationDTO;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
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
#[OA\Tag(name: 'External References')]
class AdminExternalReferenceController extends AbstractController
{
    #[Route(
        '/external-references',
        name: 'admin_external_reference_list',
        requirements: ['_format' => 'json'],
        methods: ['GET']
    )]
    #[OA\Parameter(ref: '#/components/parameters/Page')]
    #[OA\Parameter(ref: '#/components/parameters/Limit')]
    #[OA\Response(
        response: 200,
        description: 'List of External References',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ExternalReference')
                ),
                new OA\Property(
                    property: 'pagination',
                    ref: '#/components/schemas/Pagination',
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthorized')]
    #[OA\Response(response: 403, description: 'Forbidden')]
    #[Security(name: 'Bearer')]
    public function findAll(
        ExternalReferenceNormalizer $normalizer,
        MessageBusInterface $queryBus,
        Request $request,
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 50);

        $query = FindExternalReferencesQuery::withPageAndLimit($page, $limit);
        $envelope = $queryBus->dispatch($query);

        /** @var PaginatorInterface $paginator */
        $paginator = $envelope->last(HandledStamp::class)->getResult();

        $externalReferences = [];

        foreach ($paginator as $externalReference) {
            $externalReferences[] = $normalizer->normalize($externalReference);
        }

        return new JsonResponse(
            [
                'data' => $externalReferences,
                'pagination' => PaginationDTO::fromPaginator($paginator),
            ],
            Response::HTTP_OK
        );
    }
}
