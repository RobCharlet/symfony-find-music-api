<?php

namespace App\Tests\Unit\Shared\UI\EventListener;

use App\Shared\UI\EventListener\GlobalExceptionListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class GlobalExceptionListenerTest extends TestCase
{
    private GlobalExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new GlobalExceptionListener(new NullLogger());
    }

    #[Test]
    public function bailsWhenResponseAlreadySet(): void
    {
        $event = $this->createEvent(new \RuntimeException('boom'));
        $event->setResponse(new JsonResponse(['already' => 'handled'], 409));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertSame(409, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('handled', $data['already']);
    }

    #[Test]
    public function jsonExceptionReturns400(): void
    {
        $event = $this->createEvent(new \JsonException('Syntax error'));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
        $this->assertSame('Invalid JSON', $data['title']);
    }

    #[Test]
    public function httpFoundationJsonExceptionReturns400(): void
    {
        $event = $this->createEvent(new HttpFoundationJsonException('Could not decode request body.'));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function badRequestWrappingJsonExceptionReturns400(): void
    {
        $event = $this->createEvent(
            new BadRequestHttpException(
                'Could not decode request body.',
                new HttpFoundationJsonException('Could not decode request body.')
            )
        );

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function badRequestWrappingNestedJsonExceptionReturns400(): void
    {
        $jsonException = new \JsonException('Syntax error');
        $middle = new \RuntimeException('Wrapper', 0, $jsonException);
        $event = $this->createEvent(
            new BadRequestHttpException('Could not decode request body.', $middle)
        );

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('invalid_json', $data['type']);
    }

    #[Test]
    public function validationFailedReturns422(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('This value should not be blank.', '', [], '', 'email', ''),
            new ConstraintViolation('This value is too short.', '', [], '', 'password', 'ab'),
        ]);

        $event = $this->createEvent(new ValidationFailedException(new \stdClass(), $violations));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('validation_error', $data['type']);
        $this->assertSame('Validation Failed', $data['title']);
        $this->assertCount(2, $data['violations']);
        $this->assertSame('email', $data['violations'][0]['field']);
        $this->assertSame('password', $data['violations'][1]['field']);
    }

    #[Test]
    public function authenticationExceptionIsIgnored(): void
    {
        $event = $this->createEvent(new AuthenticationException('Unauthorized'));

        $this->listener->onExceptionEvent($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function accessDeniedExceptionIsIgnored(): void
    {
        $event = $this->createEvent(new AccessDeniedException('Forbidden'));

        $this->listener->onExceptionEvent($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function http4xxExceptionIsIgnored(): void
    {
        $event = $this->createEvent(new NotFoundHttpException('Not Found'));

        $this->listener->onExceptionEvent($event);

        $this->assertNull($event->getResponse());
    }

    #[Test]
    public function unhandledExceptionReturns500(): void
    {
        $event = $this->createEvent(new \RuntimeException('boom'));

        $this->listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('server_error', $data['type']);
        $this->assertSame('Internal Server Error', $data['title']);
    }

    #[Test]
    public function unhandledExceptionLogsError(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('error')
            ->with('exception.unhandled', $this->callback(
                function (array $context) {
                    return 'boom' === $context['message']
                        && \RuntimeException::class === $context['exception_class']
                        && isset($context['path'], $context['method'], $context['exception']);
                }
            ));

        $listener = new GlobalExceptionListener($mockLogger);
        $event = $this->createEvent(new \RuntimeException('boom'));

        $listener->onExceptionEvent($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());
    }

    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/api/test'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
