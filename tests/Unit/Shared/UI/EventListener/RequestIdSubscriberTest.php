<?php

namespace App\Tests\Unit\Shared\UI\EventListener;

use App\Shared\UI\EventListener\RequestIdSubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdSubscriberTest extends TestCase
{
    private RequestIdSubscriber $subscriber;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->subscriber = new RequestIdSubscriber();
        $this->kernel = $this->createStub(HttpKernelInterface::class);
    }

    #[Test]
    public function subscribesToRequestAndResponseEvents(): void
    {
        $events = RequestIdSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }

    #[Test]
    public function onKernelRequestSetsRequestIdAttribute(): void
    {
        $request = Request::create('/api/albums');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertNotEmpty($request->attributes->get('X-Request-Id'));
    }

    #[Test]
    public function onKernelRequestIgnoresSubRequests(): void
    {
        $request = Request::create('/api/albums');
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $this->subscriber->onKernelRequest($event);

        $this->assertNull($request->attributes->get('X-Request-Id'));
    }

    #[Test]
    public function onKernelResponseAddsRequestIdHeader(): void
    {
        $request = Request::create('/api/albums');
        $request->attributes->set('X-Request-Id', 'test-id-123');

        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertSame('test-id-123', $response->headers->get('X-Request-Id'));
    }

    #[Test]
    public function onKernelResponseIgnoresSubRequests(): void
    {
        $request = Request::create('/api/albums');
        $request->attributes->set('X-Request-Id', 'test-id-123');

        $response = new Response();
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $this->subscriber->onKernelResponse($event);

        $this->assertNull($response->headers->get('X-Request-Id'));
    }
}
