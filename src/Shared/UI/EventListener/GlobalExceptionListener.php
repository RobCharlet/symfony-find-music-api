<?php

namespace App\Shared\UI\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class GlobalExceptionListener
{
    #[AsEventListener(priority: -1)]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        if (null !== $event->getResponse()) {
            return;
        }

        $exception = $event->getThrowable();

        // Keep 4xx behavior handled by dedicated listeners/firewall.
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
            return;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return;
        }

        // Fallback: return JSON 500 for any unhandled exception
        $event->setResponse(new JsonResponse(
            [
                'type'   => 'server_error',
                'title'  => 'Internal Server Error',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => 'An unexpected error occurred.',
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['Content-Type' => 'application/problem+json']
        ));
    }
}
