<?php

namespace App\Shared\UI\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'health_ok')]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }
}
