<?php

namespace App\User\UI\EventListener;

use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\InvalidDiscogsAccessTokenException;
use App\User\Domain\Exception\MissingDiscogsCredentialsException;
use App\User\Domain\Exception\SodiumException;
use App\User\Domain\Exception\UserAccessForbiddenException;
use App\User\Domain\Exception\UserNotFoundException;
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
                $nested instanceof UserNotFoundException => new JsonResponse(
                    [
                        'type' => 'not_found',
                        'title' => 'Not Found',
                        'status' => Response::HTTP_NOT_FOUND,
                        'detail' => 'User not found.',
                    ],
                    Response::HTTP_NOT_FOUND,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof UserAccessForbiddenException => new JsonResponse(
                    [
                        'type' => 'forbidden',
                        'title' => 'Forbidden',
                        'status' => Response::HTTP_FORBIDDEN,
                        'detail' => 'Forbidden.',
                    ],
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof InvalidCurrentPasswordException => new JsonResponse(
                    [
                        'type' => 'invalid_current_password',
                        'title' => 'Invalid Current Password',
                        'status' => Response::HTTP_FORBIDDEN,
                        'detail' => 'Invalid current user\'s password.',
                    ],
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof UniqueConstraintViolationException => new JsonResponse(
                    [
                        'type' => 'conflict',
                        'title' => 'Conflict',
                        'status' => Response::HTTP_CONFLICT,
                        'detail' => 'A resource with the same unique constraint already exists.',
                    ],
                    Response::HTTP_CONFLICT,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof MissingDiscogsCredentialsException => new JsonResponse(
                    [
                        'type' => 'forbidden',
                        'title' => 'Forbidden',
                        'status' => Response::HTTP_FORBIDDEN,
                        'detail' => 'Missing Discogs credentials.',
                    ],
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof SodiumException => new JsonResponse(
                    [
                        'type' => 'forbidden',
                        'title' => 'Forbidden',
                        'status' => Response::HTTP_FORBIDDEN,
                        'detail' => 'Discogs credentials could not be processed.',
                    ],
                    Response::HTTP_FORBIDDEN,
                    ['Content-Type' => 'application/problem+json']
                ),
                $nested instanceof InvalidDiscogsAccessTokenException => new JsonResponse(
                    [
                        'type' => 'invalid_discogs_token',
                        'title' => 'Invalid Discogs Token',
                        'status' => Response::HTTP_BAD_REQUEST,
                        'detail' => $nested->getMessage(),
                    ],
                    Response::HTTP_BAD_REQUEST,
                    ['Content-Type' => 'application/problem+json']
                ),
                default => null,
            };

            if (null !== $response) {
                $event->setResponse($response);
            }
        }
    }
}
