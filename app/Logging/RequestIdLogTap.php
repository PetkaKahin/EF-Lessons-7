<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;

class RequestIdLogTap
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof MonologLogger) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(
                    batchMode: JsonFormatter::BATCH_MODE_NEWLINES,
                    appendNewline: true,
                    ignoreEmptyContextAndExtra: false,
                    includeStacktraces: true,
                ));
            }
        }

        $monolog->pushProcessor(static function (LogRecord $record): LogRecord {
            $requestId = $record->context['request_id'] ?? null;

            if (! is_string($requestId) || trim($requestId) === '') {
                return $record;
            }

            return $record->with(extra: [
                ...$record->extra,
                'request_id' => $requestId,
            ]);
        });
    }
}
