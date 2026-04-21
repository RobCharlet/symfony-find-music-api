<?php

namespace App\Tests\Unit\User\UI\EventListener;

use App\User\Domain\Exception\InvalidCurrentPasswordException;
use App\User\Domain\Exception\InvalidDiscogsAccessTokenException;
use App\User\Domain\Exception\MissingDiscogsCredentialsException;
use App\User\Domain\Exception\SodiumException;
use App\User\Domain\Exception\UserAccessForbiddenException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\UI\EventListener\ExceptionListener;
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
    public function userNotFoundReturnsJson404(): void
    {
        $event = $this->makeHandlerFailedEvent(new UserNotFoundException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('not_found', $data['type']);
        $this->assertSame('User not found.', $data['detail']);
    }

    #[Test]
    public function userAccessForbiddenReturnsJson403(): void
    {
        $event = $this->makeHandlerFailedEvent(new UserAccessForbiddenException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('forbidden', $data['type']);
        $this->assertSame('Forbidden.', $data['detail']);
    }

    #[Test]
    public function invalidCurrentPasswordReturnsJson403(): void
    {
        $event = $this->makeHandlerFailedEvent(new InvalidCurrentPasswordException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_current_password', $data['type']);
        $this->assertSame('Invalid current user\'s password.', $data['detail']);
    }

    #[Test]
    public function uniqueConstraintViolationReturnsJson409(): void
    {
        $nested = $this->createStub(UniqueConstraintViolationException::class);
        $event = $this->makeHandlerFailedEvent($nested);

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(409, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('conflict', $data['type']);
    }

    #[Test]
    public function missingDiscogsCredentialsReturnsJson403(): void
    {
        $event = $this->makeHandlerFailedEvent(new MissingDiscogsCredentialsException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('forbidden', $data['type']);
        $this->assertSame('Missing Discogs credentials.', $data['detail']);
    }

    #[Test]
    public function sodiumExceptionReturnsJson403(): void
    {
        $event = $this->makeHandlerFailedEvent(new SodiumException());

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('forbidden', $data['type']);
        $this->assertSame('Discogs credentials could not be processed.', $data['detail']);
    }

    #[Test]
    public function invalidDiscogsAccessTokenReturnsJson400WithMessage(): void
    {
        $event = $this->makeHandlerFailedEvent(new InvalidDiscogsAccessTokenException('Discogs access token cannot be empty'));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_discogs_token', $data['type']);
        $this->assertSame('Discogs access token cannot be empty', $data['detail']);
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
