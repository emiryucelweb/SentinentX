<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class AppKeyTest extends TestCase
{
    public function test_app_key_is_set(): void
    {
        $this->assertNotEmpty(config('app.key'), 'APP_KEY is empty in test environment');
    }
}
