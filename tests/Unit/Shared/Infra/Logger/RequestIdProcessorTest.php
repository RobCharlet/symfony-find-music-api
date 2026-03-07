<?php

namespace App\Tests\Unit\Shared\Infra\Logger;

use App\Shared\Infra\Logger\RequestIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestIdProcessorTest extends TestCase
{
    #[Test]
    public function addsRequestIdToLogRecordWhenPresent(): void
    {
        $request = Request::create('/api/albums');
        $request->attributes->set('X-Request-Id', 'abcd-1234');

        $stack = new RequestStack();
        $stack->push($request);

        $processor = new RequestIdProcessor($stack);
        $record = $this->makeRecord();

        $processed = $processor($record);

        $this->assertSame('abcd-1234', $processed->extra['request_id']);
    }

    #[Test]
    public function doesNotAddRequestIdWhenNoRequest(): void
    {
        $stack = new RequestStack();

        $processor = new RequestIdProcessor($stack);
        $record = $this->makeRecord();

        $processed = $processor($record);

        $this->assertArrayNotHasKey('request_id', $processed->extra);
    }

    #[Test]
    public function doesNotAddRequestIdWhenAttributeNotSet(): void
    {
        $request = Request::create('/api/albums');

        $stack = new RequestStack();
        $stack->push($request);

        $processor = new RequestIdProcessor($stack);
        $record = $this->makeRecord();

        $processed = $processor($record);

        $this->assertArrayNotHasKey('request_id', $processed->extra);
    }

    private function makeRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
        );
    }
}
