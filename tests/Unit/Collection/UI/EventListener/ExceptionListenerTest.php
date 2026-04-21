<?php

namespace App\Tests\Unit\Collection\UI\EventListener;

use App\Collection\Domain\Exception\AlbumNotFoundException;
use App\Collection\Domain\Exception\DiscogsIdException;
use App\Collection\Domain\Exception\ExternalReferenceNotFoundException;
use App\Collection\Domain\Exception\OwnershipForbiddenException;
use App\Collection\UI\EventListener\ExceptionListener;
use App\Collection\UI\Exception\InvalidExportFormatException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ExceptionListenerTest extends TestCase
{
    private ExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ExceptionListener();
    }

    #[Test]
    public function albumNotFoundReturns404WithCoherentBody(): void
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
    public function externalReferenceNotFoundReturns404WithCoherentBody(): void
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

    #[Test]
    public function uniqueConstraintViolationReturns409WithCoherentBody(): void
    {
        $driverException = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $nested = new UniqueConstraintViolationException($driverException, null);
        $event = $this->makeHandlerFailedEvent($nested);

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
    public function ownershipForbiddenReturns403WithCoherentBody(): void
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
    public function discogsIdExceptionReturns422WithCoherentBody(): void
    {
        $event = $this->makeHandlerFailedEvent(new DiscogsIdException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('unprocessable_entity', $data['type']);
        $this->assertSame(422, $data['status']);
        $this->assertSame('No Discogs reference found for this album.', $data['detail']);
    }

    #[Test]
    public function invalidExportFormatReturns400WithCoherentBody(): void
    {
        $event = $this->createEvent(new InvalidExportFormatException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_format', $data['type']);
        $this->assertSame(400, $data['status']);
    }

    #[Test]
    public function nonHandlerFailedExceptionIsIgnored(): void
    {
        $event = $this->createEvent(new \RuntimeException('boom'));

        $this->listener->onExceptionEvent($event);

        $this->assertNull($event->getResponse());
    }

    private function makeHandlerFailedEvent(\Throwable $nested): ExceptionEvent
    {
        $envelope = new \Symfony\Component\Messenger\Envelope(new \stdClass());
        $exception = new HandlerFailedException($envelope, [$nested]);

        return $this->createEvent($exception);
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
