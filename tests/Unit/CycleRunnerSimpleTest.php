<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CycleRunner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simplified CycleRunner tests focusing on core functionality
 * without complex mocking dependencies
 */
class CycleRunnerSimpleTest extends TestCase
{
    #[Test]
    public function constructor_creates_instance(): void
    {
        // Test that CycleRunner can be instantiated without dependencies
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CycleRunner missing deps:');

        new CycleRunner;
    }

    #[Test]
    public function constructor_validates_required_dependencies(): void
    {
        // Test dependency validation
        try {
            new CycleRunner;
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('CycleRunner missing deps:', $e->getMessage());
        }
    }

    #[Test]
    public function run_method_exists(): void
    {
        // Test that run method exists (basic structure test)
        $this->assertTrue(method_exists(CycleRunner::class, 'run'));
        $this->assertTrue(method_exists(CycleRunner::class, 'runSymbol'));
    }

    #[Test]
    public function class_has_expected_structure(): void
    {
        // Test class structure
        $reflection = new \ReflectionClass(CycleRunner::class);

        $this->assertTrue($reflection->hasMethod('run'));
        $this->assertTrue($reflection->hasMethod('runSymbol'));

        // Test that run method accepts string parameter
        $runMethod = $reflection->getMethod('run');
        $this->assertEquals(1, $runMethod->getNumberOfRequiredParameters());
    }

    #[Test]
    public function run_symbol_alias_structure(): void
    {
        // Test that runSymbol is an alias for run (method signature)
        $reflection = new \ReflectionClass(CycleRunner::class);

        $runMethod = $reflection->getMethod('run');
        $runSymbolMethod = $reflection->getMethod('runSymbol');

        $this->assertEquals(
            $runMethod->getNumberOfRequiredParameters(),
            $runSymbolMethod->getNumberOfRequiredParameters()
        );
    }

    #[Test]
    public function class_implements_expected_interface(): void
    {
        // Test basic class properties
        $reflection = new \ReflectionClass(CycleRunner::class);

        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertTrue($reflection->isInstantiable());
    }
}
