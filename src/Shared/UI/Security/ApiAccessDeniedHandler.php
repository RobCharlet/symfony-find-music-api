<?php

namespace App\Shared\UI\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Handle PHP Exception Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
        return new JsonResponse(
            [
                'type'   => 'forbidden',
                'title'  => 'Forbidden',
                'status' => Response::HTTP_FORBIDDEN,
                'detail' => 'You do not have permission to access this resource.',
            ],
            Response::HTTP_FORBIDDEN,
            ['Content-Type' => 'application/problem+json']
        );
    }
}
