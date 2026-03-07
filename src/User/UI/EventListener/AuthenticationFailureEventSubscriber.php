<?php

namespace App\User\UI\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

readonly class AuthenticationFailureEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_FAILURE => 'onFailure',
            Events::JWT_EXPIRED => 'onFailure',
            Events::JWT_INVALID => 'onFailure',
            Events::JWT_NOT_FOUND => 'onFailure',
        ];
    }

    public function onFailure(AuthenticationFailureEvent $event): void
    {
        $original = $event->getResponse();
        $statusCode = $original?->getStatusCode() ?? Response::HTTP_UNAUTHORIZED;
        $detail = $original instanceof JWTAuthenticationFailureResponse
            ? $original->getMessage()
            : 'Authentication failed.';
        $reason = match (true) {
            $event instanceof JWTExpiredEvent => 'jwt_expired',
            $event instanceof JWTInvalidEvent => 'jwt_invalid',
            $event instanceof JWTNotFoundEvent => 'jwt_not_found',
            $event->getException() instanceof TooManyLoginAttemptsAuthenticationException => 'login_throttled',
            $event->getException() instanceof BadCredentialsException => 'bad_credentials',
            default => 'auth_failure',
        };

        $event->setResponse(
            new JsonResponse(
                [
                    'type' => 'unauthorized',
                    'title' => 'Unauthorized',
                    'status' => $statusCode,
                    'detail' => $detail,
                ],
                $statusCode,
                ['Content-Type' => 'application/problem+json']
            )
        );

        $request = $event->getRequest();
        $this->logger->warning('auth.failure', [
            'reason' => $reason,
            'status' => $statusCode,
            'path'   => $request?->getPathInfo(),
            'ip'     => $request?->getClientIp(),
        ]);
    }
}
