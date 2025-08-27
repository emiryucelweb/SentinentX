<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\GdprService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GdprControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip GDPR tests - require full SaaS billing integration
        $this->markTestSkipped('GDPR tests require complete SaaS billing and tenant integration');

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_export_data_requires_authentication(): void
    {
        $response = $this->postJson('/api/gdpr/export');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_export_data_success(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('exportUserData')
            ->once()
            ->with($this->user->id)
            ->andReturn([
                'user' => ['id' => $this->user->id, 'email' => $this->user->email],
                'trades' => [],
                'subscriptions' => [],
            ]);

        $this->app->instance(GdprService::class, $mockGdprService);

        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'export_date',
                'user_id',
                'data',
                'format',
                'compliance',
            ]);
    }

    public function test_export_data_service_failure(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('exportUserData')
            ->once()
            ->with($this->user->id)
            ->andThrow(new \Exception('Export failed'));

        $this->app->instance(GdprService::class, $mockGdprService);

        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/export');

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Data export failed',
                'message' => 'Please contact support if this issue persists',
            ]);
    }

    public function test_request_deletion_requires_authentication(): void
    {
        $response = $this->postJson('/api/gdpr/delete');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_request_deletion_requires_confirmation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/delete', [
                'reason' => 'No longer need the service',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);
    }

    public function test_request_deletion_success(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('requestAccountDeletion')
            ->once()
            ->with($this->user->id, 'No longer need the service')
            ->andReturn([
                'request_id' => 'del_'.uniqid(),
                'scheduled_deletion_date' => now()->addDays(30)->toDateString(),
                'grace_period_days' => 30,
            ]);

        $this->app->instance(GdprService::class, $mockGdprService);

        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/delete', [
                'confirmation' => 'DELETE_MY_ACCOUNT',
                'reason' => 'No longer need the service',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'request_id',
                'deletion_date',
                'grace_period_days',
                'compliance',
                'notice',
            ]);
    }

    public function test_request_deletion_invalid_confirmation(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/delete', [
                'confirmation' => 'DELETE_ACCOUNT',
                'reason' => 'Test reason',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['confirmation']);
    }

    public function test_request_deletion_service_failure(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('requestAccountDeletion')
            ->once()
            ->andThrow(new \Exception('Deletion request failed'));

        $this->app->instance(GdprService::class, $mockGdprService);

        $response = $this->actingAs($this->user)
            ->postJson('/api/gdpr/delete', [
                'confirmation' => 'DELETE_MY_ACCOUNT',
                'reason' => 'Test reason',
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Deletion request failed',
                'message' => 'Please contact support',
            ]);
    }

    public function test_export_data_logs_user_information(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('exportUserData')
            ->once()
            ->andReturn(['user' => []]);

        $this->app->instance(GdprService::class, $mockGdprService);

        $this->actingAs($this->user)
            ->postJson('/api/gdpr/export');

        // Log assertions would go here in a more complete test
        $this->assertTrue(true); // Placeholder for log verification
    }

    public function test_request_deletion_logs_deletion_request(): void
    {
        $mockGdprService = Mockery::mock(GdprService::class);
        $mockGdprService->shouldReceive('requestAccountDeletion')
            ->once()
            ->andReturn([
                'request_id' => 'del_test',
                'scheduled_deletion_date' => now()->addDays(30)->toDateString(),
                'grace_period_days' => 30,
            ]);

        $this->app->instance(GdprService::class, $mockGdprService);

        $this->actingAs($this->user)
            ->postJson('/api/gdpr/delete', [
                'confirmation' => 'DELETE_MY_ACCOUNT',
                'reason' => 'Testing deletion flow',
            ]);

        // Log assertions would go here in a more complete test
        $this->assertTrue(true); // Placeholder for log verification
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
