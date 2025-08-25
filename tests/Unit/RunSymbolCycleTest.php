<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\RunSymbolCycle;
use PHPUnit\Framework\TestCase;

class RunSymbolCycleTest extends TestCase
{
    #[Test]
    public function test_run_symbol_cycle_constructor(): void
    {
        $job = new RunSymbolCycle('BTCUSDT');

        $this->assertInstanceOf(RunSymbolCycle::class, $job);
        $this->assertSame('BTCUSDT', $job->symbol);
    }

    #[Test]
    public function test_run_symbol_cycle_has_correct_properties(): void
    {
        $job = new RunSymbolCycle('ETHUSDT');

        $this->assertSame('ETHUSDT', $job->symbol);
        $this->assertSame(2, $job->tries);
        $this->assertSame([5, 15], $job->backoff);
    }

    #[Test]
    public function test_run_symbol_cycle_properties_types(): void
    {
        $job = new RunSymbolCycle('ADAUSDT');

        $reflection = new \ReflectionClass($job);

        $symbolProperty = $reflection->getProperty('symbol');
        $this->assertSame('string', $symbolProperty->getType()->getName());

        $triesProperty = $reflection->getProperty('tries');
        $this->assertSame('int', $triesProperty->getType()->getName());

        $backoffProperty = $reflection->getProperty('backoff');
        $this->assertSame('array', $backoffProperty->getType()->getName());
    }

    #[Test]
    public function test_run_symbol_cycle_unique_id(): void
    {
        $job = new RunSymbolCycle('DOTUSDT');

        $uniqueId = $job->uniqueId();

        $this->assertSame('symbol:DOTUSDT', $uniqueId);
        $this->assertIsString($uniqueId);
    }

    #[Test]
    public function test_run_symbol_cycle_unique_id_different_symbols(): void
    {
        $job1 = new RunSymbolCycle('BTCUSDT');
        $job2 = new RunSymbolCycle('ETHUSDT');

        $this->assertNotSame($job1->uniqueId(), $job2->uniqueId());
        $this->assertSame('symbol:BTCUSDT', $job1->uniqueId());
        $this->assertSame('symbol:ETHUSDT', $job2->uniqueId());
    }

    #[Test]
    public function test_run_symbol_cycle_middleware(): void
    {
        $job = new RunSymbolCycle('LINKUSDT');

        $middleware = $job->middleware();

        $this->assertIsArray($middleware);
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\WithoutOverlapping::class, $middleware[0]);
    }

    #[Test]
    public function test_run_symbol_cycle_has_handle_method(): void
    {
        $job = new RunSymbolCycle('MATICUSDT');

        $this->assertTrue(method_exists($job, 'handle'));

        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isPublic());
        $this->assertSame('void', $method->getReturnType()->getName());

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('runner', $parameters[0]->getName());
    }

    #[Test]
    public function test_run_symbol_cycle_implements_should_queue(): void
    {
        $job = new RunSymbolCycle('AVAXUSDT');

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    #[Test]
    public function test_run_symbol_cycle_uses_correct_traits(): void
    {
        $job = new RunSymbolCycle('ATOMUSDT');

        $traits = class_uses($job);

        $this->assertTrue(in_array('Illuminate\Bus\Queueable', $traits));
        $this->assertTrue(in_array('Illuminate\Queue\InteractsWithQueue', $traits));
        $this->assertTrue(in_array('Illuminate\Queue\SerializesModels', $traits));
    }

    #[Test]
    public function test_run_symbol_cycle_trading_ready(): void
    {
        $job = new RunSymbolCycle('SOLUSDT');

        // Trading essential functionality
        $this->assertTrue(method_exists($job, 'handle'));
        $this->assertTrue(method_exists($job, 'uniqueId'));
        $this->assertSame('SOLUSDT', $job->symbol);
    }

    #[Test]
    public function test_run_symbol_cycle_queue_ready(): void
    {
        $job = new RunSymbolCycle('LUNACLASSIC');

        // Queue essential functionality
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
        $this->assertTrue(method_exists($job, 'middleware'));
        $this->assertSame(2, $job->tries);
        $this->assertSame([5, 15], $job->backoff);
    }

    #[Test]
    public function test_run_symbol_cycle_concurrency_control(): void
    {
        $job = new RunSymbolCycle('UNIUSDT');

        // Concurrency control essential functionality
        $this->assertTrue(method_exists($job, 'uniqueId'));
        $this->assertTrue(method_exists($job, 'middleware'));

        $middleware = $job->middleware();
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\WithoutOverlapping::class, $middleware[0]);
    }

    #[Test]
    public function test_run_symbol_cycle_error_handling_ready(): void
    {
        $job = new RunSymbolCycle('SHIBUSDT');

        // Error handling essential functionality
        $this->assertSame(2, $job->tries);
        $this->assertSame([5, 15], $job->backoff);
        $this->assertIsArray($job->backoff);
    }

    #[Test]
    public function test_run_symbol_cycle_saas_ready(): void
    {
        $job = new RunSymbolCycle('DOGEUSDT');

        // SaaS essential functionality
        $this->assertTrue(method_exists($job, 'uniqueId'));
        $this->assertStringStartsWith('symbol:', $job->uniqueId());
    }

    #[Test]
    public function test_run_symbol_cycle_observability_ready(): void
    {
        $job = new RunSymbolCycle('XRPUSDT');

        // Observability essential functionality
        $this->assertTrue(method_exists($job, 'uniqueId'));
        $this->assertSame('XRPUSDT', $job->symbol);
    }

    #[Test]
    public function test_run_symbol_cycle_symbol_validation(): void
    {
        // Test with various symbol formats
        $symbols = ['BTCUSDT', 'ETH-USD', 'BTC_USDT', 'eth.usdt', 'ADA/USDT'];

        foreach ($symbols as $symbol) {
            $job = new RunSymbolCycle($symbol);
            $this->assertSame($symbol, $job->symbol);
            $this->assertStringContainsString($symbol, $job->uniqueId());
        }
    }

    #[Test]
    public function test_run_symbol_cycle_empty_symbol(): void
    {
        $job = new RunSymbolCycle('');

        $this->assertSame('', $job->symbol);
        $this->assertSame('symbol:', $job->uniqueId());
    }

    #[Test]
    public function test_run_symbol_cycle_special_characters_symbol(): void
    {
        $job = new RunSymbolCycle('BTC-USDT@TEST#123');

        $this->assertSame('BTC-USDT@TEST#123', $job->symbol);
        $this->assertSame('symbol:BTC-USDT@TEST#123', $job->uniqueId());
    }

    #[Test]
    public function test_run_symbol_cycle_long_symbol(): void
    {
        $longSymbol = str_repeat('A', 100);
        $job = new RunSymbolCycle($longSymbol);

        $this->assertSame($longSymbol, $job->symbol);
        $this->assertStringContainsString($longSymbol, $job->uniqueId());
    }

    #[Test]
    public function test_run_symbol_cycle_unicode_symbol(): void
    {
        $job = new RunSymbolCycle('BTC€USD₿');

        $this->assertSame('BTC€USD₿', $job->symbol);
        $this->assertSame('symbol:BTC€USD₿', $job->uniqueId());
    }
}
