<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Trading\StopCalculator;
use PHPUnit\Framework\TestCase;

class StopCalculatorTest extends TestCase
{
    private StopCalculator $stopCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stopCalculator = new StopCalculator;
    }

    #[Test]
    public function test_stop_calculator_has_compute_method(): void
    {
        $this->assertTrue(method_exists($this->stopCalculator, 'compute'));
    }

    #[Test]
    public function test_stop_calculator_has_atr_stop_method(): void
    {
        $this->assertTrue(method_exists($this->stopCalculator, 'atrStop'));
    }

    #[Test]
    public function test_stop_calculator_has_atr_take_profit_method(): void
    {
        $this->assertTrue(method_exists($this->stopCalculator, 'atrTakeProfit'));
    }

    #[Test]
    public function test_stop_calculator_has_stop_limit_method(): void
    {
        $this->assertTrue(method_exists($this->stopCalculator, 'computeStopLimit'));
    }

    #[Test]
    public function test_stop_calculator_method_signatures(): void
    {
        $reflection = new \ReflectionClass($this->stopCalculator);

        // Test compute method signature
        $computeMethod = $reflection->getMethod('compute');
        $this->assertCount(4, $computeMethod->getParameters()); // symbol, action, price, atrK

        // Test atrStop method signature
        $atrStopMethod = $reflection->getMethod('atrStop');
        $this->assertGreaterThanOrEqual(3, count($atrStopMethod->getParameters())); // action, price, atr, ...

        // Test atrTakeProfit method signature
        $atrTakeProfitMethod = $reflection->getMethod('atrTakeProfit');
        $this->assertGreaterThanOrEqual(3, count($atrTakeProfitMethod->getParameters())); // action, price, atr, ...
    }

    #[Test]
    public function test_stop_calculator_method_visibility(): void
    {
        $reflection = new \ReflectionClass($this->stopCalculator);

        // Public methods for API
        $this->assertTrue($reflection->getMethod('compute')->isPublic());
        $this->assertTrue($reflection->getMethod('atrStop')->isPublic());
        $this->assertTrue($reflection->getMethod('atrTakeProfit')->isPublic());
        $this->assertTrue($reflection->getMethod('computeStopLimit')->isPublic());
    }

    #[Test]
    public function test_stop_calculator_saas_trading_service(): void
    {
        // Verify it's a service class suitable for SaaS
        $reflection = new \ReflectionClass($this->stopCalculator);

        $this->assertFalse($reflection->isFinal()); // Service should be extensible
        $this->assertTrue($reflection->hasMethod('compute'));
        $this->assertTrue($reflection->hasMethod('atrStop'));
        $this->assertTrue($reflection->hasMethod('atrTakeProfit'));
    }

    #[Test]
    public function test_stop_calculator_risk_management_methods(): void
    {
        // Core risk management methods
        $methods = ['compute', 'atrStop', 'atrTakeProfit', 'computeStopLimit'];

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->stopCalculator, $method), "Method {$method} should exist");
        }
    }
}
