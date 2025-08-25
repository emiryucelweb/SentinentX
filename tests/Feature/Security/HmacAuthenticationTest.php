<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\RequiresRedis;
use Tests\TestCase;

class HmacAuthenticationTest extends TestCase
{
    use RequiresRedis;

    private string $secret = 'test-hmac-secret-key-12345';

    private string $baseUrl = '/api/admin/health';

    protected function setUp(): void
    {
        parent::setUp();

        // Set up HMAC config
        Config::set('security.hmac_secret', $this->secret);

        // Use trait method for consistent Redis handling
        $this->requireRedis();
        $this->cleanRedis();
    }

    #[Test]
    public function hmac_valid_signature_returns_200()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);
        $method = 'GET';
        $path = '/api/admin/health';
        $query = '';
        $body = '';
        $contentHash = hash('sha256', $body);

        $payload = implode("\n", [
            $method,
            $path,
            $query,
            $timestamp,
            $nonce,
            $contentHash,
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secret);

        $response = $this->get($this->baseUrl, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(200);

        // Verify nonce is stored in Redis
        $this->assertTrue(Redis::exists("hmac_nonce:{$nonce}") > 0);
    }

    #[Test]
    public function hmac_bad_signature_returns_401()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);
        $badSignature = 'invalid_signature_12345';

        $response = $this->get($this->baseUrl, [
            'X-Signature' => $badSignature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    #[Test]
    public function hmac_old_timestamp_returns_401()
    {
        $oldTimestamp = (string) (time() - 400); // 400 seconds ago (>300s limit)
        $nonce = uniqid('test_nonce_', true);

        // Generate valid signature for old timestamp
        $payload = "GET\n/api/admin/health\n\n{$oldTimestamp}\n{$nonce}\n".hash('sha256', '');
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $response = $this->get($this->baseUrl, [
            'X-Signature' => $signature,
            'X-Timestamp' => $oldTimestamp,
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Request timestamp expired']);
    }

    #[Test]
    public function hmac_replay_nonce_returns_401()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);

        // First request - should succeed
        $payload = "GET\n/api/admin/health\n\n{$timestamp}\n{$nonce}\n".hash('sha256', '');
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $firstResponse = $this->get($this->baseUrl, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);
        $firstResponse->assertStatus(200);

        // Second request with same nonce - should fail
        $secondResponse = $this->get($this->baseUrl, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $secondResponse->assertStatus(401);
        $secondResponse->assertJson(['error' => 'Nonce already used (replay attack detected)']);
    }

    #[Test]
    public function hmac_missing_headers_returns_401()
    {
        // Missing X-Signature
        $response = $this->get($this->baseUrl, [
            'X-Timestamp' => (string) time(),
            'X-Nonce' => uniqid('test_nonce_', true),
        ]);
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing required headers: X-Signature, X-Timestamp']);

        // Missing X-Nonce
        $response = $this->get($this->baseUrl, [
            'X-Signature' => 'some_signature',
            'X-Timestamp' => (string) time(),
        ]);
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Missing X-Nonce header']);
    }

    #[Test]
    public function hmac_query_canonicalization_works()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);
        $method = 'GET';
        $path = '/api/admin/metrics';

        // Test with unsorted query parameters
        $unsortedQuery = 'param2=value2&param1=value1&param3=value3';
        $sortedQuery = 'param1=value1&param2=value2&param3=value3';

        $body = '';
        $contentHash = hash('sha256', $body);

        $payload = implode("\n", [
            $method,
            $path,
            $sortedQuery, // Should be canonicalized
            $timestamp,
            $nonce,
            $contentHash,
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secret);

        $response = $this->get("/api/admin/metrics?{$unsortedQuery}", [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function hmac_post_with_body_hash_works()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);
        $method = 'POST';
        $path = '/api/admin/shutdown';
        $body = json_encode(['reason' => 'maintenance']);
        $contentHash = hash('sha256', $body);

        $payload = implode("\n", [
            $method,
            $path,
            '', // No query
            $timestamp,
            $nonce,
            $contentHash,
        ]);

        $signature = hash_hmac('sha256', $payload, $this->secret);

        $response = $this->postJson('/api/admin/shutdown', json_decode($body, true), [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function redis_nonce_cache_has_correct_ttl()
    {
        $timestamp = (string) time();
        $nonce = uniqid('test_nonce_', true);

        $payload = "GET\n/api/admin/health\n\n{$timestamp}\n{$nonce}\n".hash('sha256', '');
        $signature = hash_hmac('sha256', $payload, $this->secret);

        $this->get($this->baseUrl, [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ]);

        $nonceKey = "hmac_nonce:{$nonce}";
        $ttl = Redis::ttl($nonceKey);

        // TTL should be around 300 seconds (±5 seconds for test execution time)
        $this->assertGreaterThan(295, $ttl);
        $this->assertLessThan(305, $ttl);
    }
}
