<?php

namespace App\User\UI\EventListener;

use App\User\Domain\UserNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

class ExceptionListener
{
    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getPrevious();

            if ($nested instanceof UserNotFoundException) {
                $event->setResponse(new JsonResponse(
                    [
                        'type'   => 'not_found',
                        'title'  => 'Not Found',
                        'status' => Response::HTTP_NOT_FOUND,
                        'detail' => 'User not found.',
                    ],
                    Response::HTTP_NOT_FOUND,
                    ['Content-Type' => 'application/problem+json']
                ));

                return;
            }

            if ($nested instanceof UniqueConstraintViolationException) {
                $event->setResponse(new JsonResponse(
                    [
                        'type'   => 'conflict',
                        'title'  => 'Conflict',
                        'status' => Response::HTTP_CONFLICT,
                        'detail' => 'A resource with the same unique constraint already exists.',
                    ],
                    Response::HTTP_CONFLICT,
                    ['Content-Type' => 'application/problem+json']
                ));

                return;
            }
        }

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
                    'type'   => 'invalid_json',
                    'title'  => 'Invalid JSON',
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
                    'field'   => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse(
                [
                    'type'       => 'validation_error',
                    'title'      => 'Validation Failed',
                    'status'     => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'detail'     => 'One or more fields are invalid.',
                    'violations' => $violations,
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['Content-Type' => 'application/problem+json']
            ));
        }
    }
}
