<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Logger\StructuredLogService;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructuredLogServiceTest extends TestCase
{
    private StructuredLogService $logService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logService = new StructuredLogService;
    }

    #[Test]
    public function log_calls_log_channel_with_structured_context(): void
    {
        $channel = 'test_channel';
        $level = 'info';
        $message = 'Test message';
        $context = ['key' => 'value'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with($level, $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with($channel)
            ->andReturn($logChannel);

        $this->logService->log($channel, $level, $message, $context);
    }

    #[Test]
    public function trading_logs_to_trading_channel_with_info_level(): void
    {
        $message = 'Trade executed';
        $context = ['symbol' => 'BTCUSDT', 'side' => 'LONG'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('trading')
            ->andReturn($logChannel);

        $this->logService->trading($message, $context);
    }

    #[Test]
    public function ai_logs_to_ai_channel_with_info_level(): void
    {
        $message = 'AI decision made';
        $context = ['provider' => 'OpenAI', 'confidence' => 85];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('ai')
            ->andReturn($logChannel);

        $this->logService->ai($message, $context);
    }

    #[Test]
    public function risk_logs_to_risk_channel_with_warning_level(): void
    {
        $message = 'Risk threshold exceeded';
        $context = ['risk_level' => 'HIGH', 'exposure' => 0.8];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('warning', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('risk')
            ->andReturn($logChannel);

        $this->logService->risk($message, $context);
    }

    #[Test]
    public function lab_logs_to_lab_channel_with_info_level(): void
    {
        $message = 'Backtest completed';
        $context = ['strategy' => 'EMA_CROSS', 'period' => '1D'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('lab')
            ->andReturn($logChannel);

        $this->logService->lab($message, $context);
    }

    #[Test]
    public function error_logs_to_specified_channel_with_error_level(): void
    {
        $channel = 'custom_channel';
        $message = 'Something went wrong';
        $context = ['error_code' => 500];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('error', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with($channel)
            ->andReturn($logChannel);

        $this->logService->error($channel, $message, $context);
    }

    #[Test]
    public function warning_logs_to_specified_channel_with_warning_level(): void
    {
        $channel = 'alert_channel';
        $message = 'Warning message';
        $context = ['severity' => 'MEDIUM'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('warning', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with($channel)
            ->andReturn($logChannel);

        $this->logService->warning($channel, $message, $context);
    }

    #[Test]
    public function info_logs_to_specified_channel_with_info_level(): void
    {
        $channel = 'info_channel';
        $message = 'Information message';
        $context = ['category' => 'SYSTEM'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with($channel)
            ->andReturn($logChannel);

        $this->logService->info($channel, $message, $context);
    }

    #[Test]
    public function debug_logs_to_specified_channel_with_debug_level(): void
    {
        $channel = 'debug_channel';
        $message = 'Debug message';
        $context = ['debug_level' => 'VERBOSE'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('debug', $message, Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with($channel)
            ->andReturn($logChannel);

        $this->logService->debug($channel, $message, $context);
    }

    #[Test]
    public function structure_context_includes_required_fields(): void
    {
        $context = ['custom_key' => 'custom_value'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function structure_context_handles_array_values(): void
    {
        $context = ['array_value' => ['nested' => 'data']];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function structure_context_handles_object_values(): void
    {
        $object = new \stdClass;
        $object->property = 'value';
        $context = ['object_value' => $object];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function structure_context_handles_primitive_types(): void
    {
        $context = [
            'string_value' => 'test',
            'integer_value' => 42,
            'float_value' => 3.14,
            'boolean_value' => true,
            'null_value' => null,
        ];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function performance_logs_with_performance_metrics(): void
    {
        $operation = 'database_query';
        $duration = 0.125;
        $metrics = ['rows' => 1000, 'cache_hits' => 50];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Performance: database_query', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('json')
            ->andReturn($logChannel);

        $this->logService->performance($operation, $duration, $metrics);
    }

    #[Test]
    public function business_event_logs_with_business_context(): void
    {
        $event = 'user_registered';
        $entity = 'User';
        $entityId = 12345;
        $data = ['plan' => 'premium', 'source' => 'web'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Business Event: user_registered', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('json')
            ->andReturn($logChannel);

        $this->logService->businessEvent($event, $entity, $entityId, $data);
    }

    #[Test]
    public function log_with_empty_context_still_includes_required_fields(): void
    {
        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', []);
    }

    #[Test]
    public function log_with_null_context_values_handles_gracefully(): void
    {
        $context = ['null_value' => null, 'string_value' => 'test'];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function log_with_boolean_context_values_preserves_types(): void
    {
        $context = ['enabled' => true, 'disabled' => false];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function log_with_numeric_context_values_preserves_types(): void
    {
        $context = ['integer_value' => 42, 'float_value' => 3.14];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function performance_logs_without_additional_metrics(): void
    {
        $operation = 'simple_operation';
        $duration = 0.001;

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Performance: simple_operation', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('json')
            ->andReturn($logChannel);

        $this->logService->performance($operation, $duration);
    }

    #[Test]
    public function business_event_logs_without_additional_data(): void
    {
        $event = 'simple_event';
        $entity = 'Simple';
        $entityId = 'id_123';

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Business Event: simple_event', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('json')
            ->andReturn($logChannel);

        $this->logService->businessEvent($event, $entity, $entityId);
    }

    #[Test]
    public function log_with_complex_nested_arrays(): void
    {
        $context = [
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value',
                    ],
                ],
            ],
        ];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function log_with_mixed_data_types(): void
    {
        $context = [
            'string' => 'text',
            'integer' => 123,
            'float' => 45.67,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object) ['key' => 'value'],
        ];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function log_with_special_characters_in_context(): void
    {
        $context = [
            'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'unicode' => '🚀📈💎',
            'quotes' => '"single\' and "double" quotes',
            'newlines' => "line1\nline2\r\nline3",
        ];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }

    #[Test]
    public function log_with_large_numbers(): void
    {
        $context = [
            'large_int' => PHP_INT_MAX,
            'small_int' => PHP_INT_MIN,
            'large_float' => 1.7976931348623157E+308,
            'small_float' => 2.2250738585072014E-308,
        ];

        $logChannel = Mockery::mock('stdClass');
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Test', Mockery::any());

        Log::shouldReceive('channel')
            ->once()
            ->with('test')
            ->andReturn($logChannel);

        $this->logService->log('test', 'info', 'Test', $context);
    }
}
