<?php

namespace App\Collection\UI\EventListener;

use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\UI\Exception\InvalidExportFormatException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ExceptionListener
{
    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getPrevious();

            $response = match (true) {
                $nested instanceof AlbumNotFoundException => new JsonResponse(
                    [
                        'type' => 'not_found',
                        'title' => 'Not Found',
                        'status' => Response::HTTP_NOT_FOUND,
                        'detail' => 'Album not found.',
                    ],
                    Response::HTTP_NOT_FOUND,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof ExternalReferenceNotFoundException => new JsonResponse(
                    [
                        'type' => 'not_found',
                        'title' => 'Not Found',
                        'status' => Response::HTTP_NOT_FOUND,
                        'detail' => 'External reference not found.',
                    ],
                    Response::HTTP_NOT_FOUND,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof UniqueConstraintViolationException => new JsonResponse(
                    ['type' => 'conflict',
                        'title' => 'Conflict',
                        'status' => Response::HTTP_CONFLICT,
                        'detail' => 'A resource with the same unique constraint already exists.', ],
                    Response::HTTP_CONFLICT,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof OwnershipForbiddenException => new JsonResponse(
                    [
                        'type' => 'forbidden',
                        'title' => 'Forbidden',
                        'status' => Response::HTTP_FORBIDDEN,
                        'detail' => 'Forbidden.',
                    ],
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'application/problem+json']
                ),
                default => null,
            };

            if (null !== $response) {
                $event->setResponse($response);
            }
        }

        if ($exception instanceof InvalidExportFormatException) {
            $event->setResponse(new JsonResponse(
                [
                    'type' => 'invalid_format',
                    'title' => 'Bad Request',
                    'status' => Response::HTTP_BAD_REQUEST,
                    'detail' => 'Invalid export format.',
                ],
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/problem+json']
            ));
        }
    }
}
