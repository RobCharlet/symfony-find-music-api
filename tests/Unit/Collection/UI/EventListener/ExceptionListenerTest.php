<?php

namespace App\Tests\Unit\Collection\UI\EventListener;

use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\UI\EventListener\ExceptionListener;
use App\Shared\UI\EventListener\GlobalExceptionListener;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ExceptionListenerTest extends TestCase
{
    private ExceptionListener $listener;
    private GlobalExceptionListener $globalExceptionListener;

    protected function setUp(): void
    {
        $this->listener = new ExceptionListener();
        $this->globalExceptionListener = new GlobalExceptionListener();
    }

    #[Test]
    public function unhandledExceptionReturnsJson500()
    {
        $request = Request::create('/something');
        $kernel  = $this->createStub(HttpKernelInterface::class);
        $event   = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('boom')
        );

        $this->globalExceptionListener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('server_error', $data['type']);
        $this->assertSame('Internal Server Error', $data['title']);
    }

    #[Test]
    public function badRequestWrappingJsonExceptionReturnsInvalidJsonResponse()
    {
        $request = Request::create('/something');
        $kernel  = $this->createStub(HttpKernelInterface::class);
        $event   = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new BadRequestHttpException(
                'Could not decode request body.',
                new HttpFoundationJsonException('Could not decode request body.')
            )
        );

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function uniqueConstraintViolationReturns409WithCoherentBody()
    {
        $driverException = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $nested          = new UniqueConstraintViolationException($driverException, null);
        $event           = $this->makeHandlerFailedEvent($nested);

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('conflict', $data['type']);
        $this->assertSame(409, $data['status']);
    }

    #[Test]
    public function ownershipForbiddenReturns403WithCoherentBody()
    {
        $event = $this->makeHandlerFailedEvent(new OwnershipForbiddenException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('forbidden', $data['type']);
        $this->assertSame(403, $data['status']);
    }

    #[Test]
    public function albumNotFoundReturns404WithCoherentBody()
    {
        $event = $this->makeHandlerFailedEvent(new AlbumNotFoundException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('not_found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    #[Test]
    public function externalReferenceNotFoundReturns404WithCoherentBody()
    {
        $event = $this->makeHandlerFailedEvent(new ExternalReferenceNotFoundException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('not_found', $data['type']);
        $this->assertSame(404, $data['status']);
    }

    private function makeHandlerFailedEvent(\Throwable $nested): ExceptionEvent
    {
        $envelope  = new \Symfony\Component\Messenger\Envelope(new \stdClass());
        $exception = new HandlerFailedException($envelope, [$nested]);
        $kernel    = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
