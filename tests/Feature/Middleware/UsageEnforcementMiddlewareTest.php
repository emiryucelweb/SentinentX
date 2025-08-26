<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\UsageEnforcementMiddleware;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UsageCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UsageEnforcementMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private UsageEnforcementMiddleware $middleware;

    private User $user;

    private Tenant $tenant;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new UsageEnforcementMiddleware();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan' => 'starter',
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        // Configure test billing plans
        Config::set('billing.plans.starter.limits', [
            'api_requests' => 1000,
            'trades' => 50,
            'ai_requests' => 100,
        ]);

        Config::set('billing.plans.professional.limits', [
            'api_requests' => 10000,
            'trades' => 500,
            'ai_requests' => 1000,
        ]);
    }

    public function test_middleware_requires_authenticated_user(): void
    {
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'api_requests');

        $this->assertEquals(400, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Tenant context required', $responseData['error']);
        $this->assertEquals('TENANT_REQUIRED', $responseData['code']);
    }

    public function test_middleware_requires_active_subscription(): void
    {
        $this->subscription->update(['status' => 'cancelled']);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'api_requests');

        $this->assertEquals(402, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Active subscription required', $responseData['error']);
        $this->assertEquals('SUBSCRIPTION_REQUIRED', $responseData['code']);
    }

    public function test_middleware_handles_invalid_plan(): void
    {
        $this->subscription->update(['plan' => 'nonexistent_plan']);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'api_requests');

        $this->assertEquals(500, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid subscription plan', $responseData['error']);
        $this->assertEquals('INVALID_PLAN', $responseData['code']);
    }

    public function test_middleware_allows_request_within_limits(): void
    {
        // Create usage that's below the limit (starter plan has 1000 api_requests)
        UsageCounter::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_type' => 'api_requests',
            'count' => 100,
            'period_start' => now()->startOfMonth(),
        ]);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'api_requests');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());

        // Verify usage headers are set
        $this->assertEquals('api_requests', $response->headers->get('X-Usage-Type'));
        $this->assertEquals('100', $response->headers->get('X-Usage-Current'));
        $this->assertEquals('1000', $response->headers->get('X-Usage-Limit'));
        $this->assertEquals('900', $response->headers->get('X-Usage-Remaining'));
    }

    public function test_middleware_blocks_request_over_limits(): void
    {
        // Create usage that exceeds the limit
        UsageCounter::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_type' => 'api_requests',
            'count' => 1000, // Starter plan limit
            'period_start' => now()->startOfMonth(),
        ]);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'api_requests');

        $this->assertEquals(429, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Usage limit exceeded', $responseData['error']);
        $this->assertEquals('USAGE_LIMIT_EXCEEDED', $responseData['code']);

        $this->assertArrayHasKey('details', $responseData);
        $this->assertEquals('api_requests', $responseData['details']['usage_type']);
        $this->assertEquals(1000, $responseData['details']['current_usage']);
        $this->assertEquals(1000, $responseData['details']['limit']);
        $this->assertEquals('starter', $responseData['details']['plan']);
    }

    public function test_middleware_handles_unlimited_usage_type(): void
    {
        // Configure a plan with unlimited usage
        Config::set('billing.plans.starter.limits.unlimited_feature', -1);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'unlimited_feature');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('-1', $response->headers->get('X-Usage-Limit'));
    }

    public function test_middleware_increments_usage_on_success(): void
    {
        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'api_requests');

        // Check that usage was incremented
        $usage = UsageCounter::where('tenant_id', $this->tenant->id)
                            ->where('usage_type', 'api_requests')
                            ->where('period_start', now()->startOfMonth())
                            ->first();

        $this->assertNotNull($usage);
        $this->assertEquals(1, $usage->count);
        $this->assertNotNull($usage->last_used_at);
    }

    public function test_middleware_does_not_increment_usage_on_error(): void
    {
        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $this->middleware->handle($request, function () {
            return new Response('Server Error', 500);
        }, 'api_requests');

        // Check that usage was not incremented
        $usage = UsageCounter::where('tenant_id', $this->tenant->id)
                            ->where('usage_type', 'api_requests')
                            ->where('period_start', now()->startOfMonth())
                            ->first();

        $this->assertNull($usage);
    }

    public function test_middleware_handles_multiple_usage_types(): void
    {
        Auth::login($this->user);

        // Test different usage types
        $usageTypes = ['api_requests', 'trades', 'ai_requests'];

        foreach ($usageTypes as $usageType) {
            $request = Request::create('/api/test', 'POST');
            
            $response = $this->middleware->handle($request, function () {
                return new Response('OK', 200);
            }, $usageType);

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($usageType, $response->headers->get('X-Usage-Type'));
        }

        // Verify each usage type was tracked separately
        foreach ($usageTypes as $usageType) {
            $usage = UsageCounter::where('tenant_id', $this->tenant->id)
                                ->where('usage_type', $usageType)
                                ->first();
            
            $this->assertNotNull($usage);
            $this->assertEquals(1, $usage->count);
        }
    }

    public function test_middleware_handles_period_rollover(): void
    {
        // Create usage from previous month
        UsageCounter::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_type' => 'api_requests',
            'count' => 500,
            'period_start' => now()->subMonth()->startOfMonth(),
        ]);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'api_requests');

        $this->assertEquals(200, $response->getStatusCode());
        
        // Should show usage as 0 for current month
        $this->assertEquals('0', $response->headers->get('X-Usage-Current'));
    }

    public function test_middleware_aggregates_usage_across_multiple_records(): void
    {
        // Create multiple usage records for the same period
        UsageCounter::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_type' => 'api_requests',
            'count' => 300,
            'period_start' => now()->startOfMonth(),
        ]);

        UsageCounter::factory()->create([
            'tenant_id' => $this->tenant->id,
            'usage_type' => 'api_requests',
            'count' => 200,
            'period_start' => now()->startOfMonth(),
        ]);

        Auth::login($this->user);
        $request = Request::create('/api/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('OK', 200);
        }, 'api_requests');

        $this->assertEquals(200, $response->getStatusCode());
        
        // Should aggregate to 500
        $this->assertEquals('500', $response->headers->get('X-Usage-Current'));
        $this->assertEquals('500', $response->headers->get('X-Usage-Remaining'));
    }
}
