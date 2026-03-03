<?php

namespace App\User\UI\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationFailureEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onFailure',
            Events::JWT_EXPIRED            => 'onFailure',
            Events::JWT_INVALID            => 'onFailure',
            Events::JWT_NOT_FOUND          => 'onFailure',
        ];
    }

    public function onFailure(AuthenticationFailureEvent $event): void
    {
        $original   = $event->getResponse();
        $statusCode = $original?->getStatusCode() ?? Response::HTTP_UNAUTHORIZED;
        $detail     = $original instanceof JWTAuthenticationFailureResponse
            ? $original->getMessage()
            : 'Authentication failed.';

        $event->setResponse(
            new JsonResponse(
                [
                    'type'   => 'unauthorized',
                    'title'  => 'Unauthorized',
                    'status' => $statusCode,
                    'detail' => $detail,
                ],
                $statusCode,
                ['Content-Type' => 'application/problem+json']
            )
        );
    }
}
