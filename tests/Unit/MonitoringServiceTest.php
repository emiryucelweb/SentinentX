<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Monitoring\MonitoringService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringServiceTest extends TestCase
{
    #[Test]
    public function service_class_exists()
    {
        $this->assertTrue(class_exists(MonitoringService::class));
    }

    #[Test]
    public function service_has_required_methods()
    {
        $this->assertTrue(method_exists(MonitoringService::class, 'checkSystemHealth'));
    }
}
