<?php

namespace App\Tests\Unit\User\UI\EventListener;

use App\Shared\UI\EventListener\GlobalExceptionListener;
use App\User\Domain\UserNotFoundException;
use App\User\UI\EventListener\ExceptionListener;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ExceptionListenerTest extends TestCase
{
    private ExceptionListener $listener;
    private GlobalExceptionListener $globalListener;

    protected function setUp(): void
    {
        $this->listener = new ExceptionListener();
        $this->globalListener = new GlobalExceptionListener();
    }

    #[Test]
    public function userNotFoundReturnsJson404(): void
    {
        $nested = new UserNotFoundException();
        $exception = new HandlerFailedException(
            new \Symfony\Component\Messenger\Envelope(new \stdClass()),
            [$nested],
        );

        $event = $this->createEvent($exception);
        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('not_found', $data['type']);
        $this->assertSame('User not found.', $data['detail']);
    }

    #[Test]
    public function uniqueConstraintViolationReturnsJson409(): void
    {
        $nested = $this->createStub(UniqueConstraintViolationException::class);
        $exception = new HandlerFailedException(
            new \Symfony\Component\Messenger\Envelope(new \stdClass()),
            [$nested],
        );

        $event = $this->createEvent($exception);
        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(409, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('conflict', $data['type']);
    }

    #[Test]
    public function jsonExceptionReturnsJson400(): void
    {
        $event = $this->createEvent(new \JsonException('Syntax error'));
        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function badRequestWrappingJsonExceptionReturnsJson400(): void
    {
        $event = $this->createEvent(
            new BadRequestHttpException('Could not decode request body.', new HttpFoundationJsonException('Could not decode request body.'))
        );
        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function validationFailedReturnsJson422(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should not be blank.', '', [], '', 'email', ''),
        ]);

        $command = new \stdClass();
        $exception = new ValidationFailedException($command, $violations);

        $event = $this->createEvent($exception);
        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $this->assertArrayHasKey('violations', $data);
        $this->assertSame('email', $data['violations'][0]['field']);
    }

    #[Test]
    public function unhandledExceptionReturnsJson500(): void
    {
        $event = $this->createEvent(new \RuntimeException('boom'));
        $this->globalListener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('server_error', $data['type']);
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/something'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
