<?php

namespace App\Shared\Infra\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RequestIdProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $requestId = $request->attributes->get('X-Request-Id');

            if (is_string($requestId)) {
                $record->extra['request_id'] = $requestId;
            }
        }

        return $record;
    }
}
