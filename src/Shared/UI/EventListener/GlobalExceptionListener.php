<?php

namespace App\Shared\UI\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Last-resort exception listener (priority -1).
 *
 * Runs after bounded-context listeners (Collection, User) which handle
 * their own domain exceptions at default priority (0).
 *
 * Order of checks:
 *  1. Bail if a higher-priority listener already set a response
 *  2. JSON parse errors → 400
 *  3. Messenger validation → 422
 *  4. Auth / 4xx HTTP exceptions → let Symfony handle natively
 *  5. Anything else → 500 fallback
 */
class GlobalExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(priority: -1)]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // A bounded-context listener already handled this exception.
        if (null !== $event->getResponse()) {
            return;
        }

        $exception = $event->getThrowable();

        if (
            $exception instanceof \JsonException
            || $exception instanceof HttpFoundationJsonException
            || ($exception instanceof BadRequestHttpException
                && (
                    $exception->getPrevious() instanceof \JsonException
                    || $exception->getPrevious() instanceof HttpFoundationJsonException
                    || $exception->getPrevious()?->getPrevious() instanceof \JsonException
                ))
        ) {
            $jsonException = $exception;

            if ($exception instanceof BadRequestHttpException && null !== $exception->getPrevious()) {
                $jsonException = $exception->getPrevious();
            }

            $event->setResponse(new JsonResponse(
                [
                    'type' => 'invalid_json',
                    'title' => 'Invalid JSON',
                    'status' => Response::HTTP_BAD_REQUEST,
                    'detail' => $jsonException->getMessage(),
                ],
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/problem+json']
            ));

            return;
        }

        if ($exception instanceof ValidationFailedException) {
            $violations = [];
            foreach ($exception->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse(
                [
                    'type' => 'validation_error',
                    'title' => 'Validation Failed',
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'detail' => 'One or more fields are invalid.',
                    'violations' => $violations,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['Content-Type' => 'application/problem+json']
            ));

            return;
        }

        // Let Symfony's firewall/error handling deal with auth and other 4xx natively.
        if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
            return;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return;
        }

        $this->logger->error('exception.unhandled', [
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
            'path' => $event->getRequest()->getPathInfo(),
            'method' => $event->getRequest()->getMethod(),
            'exception' => $exception,
        ]);

        // Fallback: return JSON 500 for any unhandled exception
        $event->setResponse(new JsonResponse(
            [
                'type' => 'server_error',
                'title' => 'Internal Server Error',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'detail' => 'An unexpected error occurred.',
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['Content-Type' => 'application/problem+json']
        ));
    }
}
