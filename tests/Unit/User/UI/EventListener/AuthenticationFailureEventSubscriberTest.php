<?php

namespace App\Tests\Unit\User\UI\EventListener;

use App\User\UI\EventListener\AuthenticationFailureEventSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class AuthenticationFailureEventSubscriberTest extends TestCase
{
    #[Test]
    public function onFailureReturnsProblemJsonResponse(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())->method('warning');

        $event = new AuthenticationFailureEvent(new BadCredentialsException(), null, null);

        $subscriber = new AuthenticationFailureEventSubscriber($mockLogger);
        $subscriber->onFailure($event);

        $response = $event->getResponse();
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertSame('unauthorized', $data['type']);
        $this->assertSame('Unauthorized', $data['title']);
        $this->assertSame(401, $data['status']);
    }

    #[Test]
    public function onFailureLogsBadCredentialsReason(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('auth.failure', $this->callback(fn (array $ctx) => 'bad_credentials' === $ctx['reason']));

        $event = new AuthenticationFailureEvent(new BadCredentialsException(), null, null);

        $subscriber = new AuthenticationFailureEventSubscriber($mockLogger);
        $subscriber->onFailure($event);
    }

    #[Test]
    public function onFailureLogsJwtExpiredReason(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('auth.failure', $this->callback(fn (array $ctx) => 'jwt_expired' === $ctx['reason']));

        $event = new JWTExpiredEvent(new BadCredentialsException(), null, null);

        $subscriber = new AuthenticationFailureEventSubscriber($mockLogger);
        $subscriber->onFailure($event);
    }

    #[Test]
    public function onFailureLogsJwtNotFoundReason(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('auth.failure', $this->callback(fn (array $ctx) => 'jwt_not_found' === $ctx['reason']));

        $event = new JWTNotFoundEvent(new BadCredentialsException(), null, null);

        $subscriber = new AuthenticationFailureEventSubscriber($mockLogger);
        $subscriber->onFailure($event);
    }

    #[Test]
    public function onFailureLogsRequestPathAndIp(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('warning')
            ->with('auth.failure', $this->callback(function (array $ctx) {
                return '/api/albums' === $ctx['path'] && '127.0.0.1' === $ctx['ip'];
            }));

        $request = Request::create('/api/albums', 'GET', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $event = new AuthenticationFailureEvent(new BadCredentialsException(), null, $request);

        $subscriber = new AuthenticationFailureEventSubscriber($mockLogger);
        $subscriber->onFailure($event);
    }
}
