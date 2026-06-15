<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\RequestIdLogTap;
use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RequestIdLogTapTest extends TestCase
{
    public function test_it_formats_records_as_json_and_exposes_request_id(): void
    {
        $handler = new TestHandler;
        $logger = new IlluminateLogger(new MonologLogger('testing', [$handler]));

        (new RequestIdLogTap)($logger);

        $logger
            ->withContext(['request_id' => 'lesson-request-id'])
            ->info('Task list requested', ['status' => 'new']);

        $records = $handler->getRecords();

        $this->assertCount(1, $records);
        $this->assertInstanceOf(JsonFormatter::class, $handler->getFormatter());
        $this->assertSame('lesson-request-id', $records[0]->context['request_id']);
        $this->assertSame('lesson-request-id', $records[0]->extra['request_id']);

        $formatted = json_decode((string) $records[0]->formatted, true);

        $this->assertIsArray($formatted);
        $this->assertSame('Task list requested', $formatted['message']);
        $this->assertSame('lesson-request-id', $formatted['context']['request_id']);
        $this->assertSame('lesson-request-id', $formatted['extra']['request_id']);
    }

    public function test_it_exposes_request_id_on_error_records(): void
    {
        $handler = new TestHandler;
        $logger = new IlluminateLogger(new MonologLogger('testing', [$handler]));

        (new RequestIdLogTap)($logger);

        $logger
            ->withContext(['request_id' => 'failed-request-id'])
            ->error('Unhandled exception', [
                'exception' => new RuntimeException('Something failed.'),
            ]);

        $records = $handler->getRecords();

        $this->assertCount(1, $records);
        $this->assertSame('ERROR', $records[0]->level->getName());
        $this->assertSame('failed-request-id', $records[0]->context['request_id']);
        $this->assertSame('failed-request-id', $records[0]->extra['request_id']);

        $formatted = json_decode((string) $records[0]->formatted, true);

        $this->assertIsArray($formatted);
        $this->assertSame('Unhandled exception', $formatted['message']);
        $this->assertSame('ERROR', $formatted['level_name']);
        $this->assertSame('failed-request-id', $formatted['context']['request_id']);
        $this->assertSame('failed-request-id', $formatted['extra']['request_id']);
    }
}
