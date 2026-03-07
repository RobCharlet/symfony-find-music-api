<?php

namespace App\Shared\UI\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\UuidV7;

readonly class RequestIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Must be fired before Symfony Firewall.
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            // Must be fired after any other listener.
            KernelEvents::RESPONSE => ['onKernelResponse', -1000],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Filter Client request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = UuidV7::generate();

        $request->attributes->set('X-Request-Id', $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->attributes->get('X-Request-Id');

        if ($requestId && is_string($requestId)) {
            $event->getResponse()->headers->set('X-Request-Id', $requestId);
        }
    }
}
