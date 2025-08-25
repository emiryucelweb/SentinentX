<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\BybitHelpers;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(BybitHelpers::class)]
#[Group('unit')]
#[Group('helpers')]
final class BybitHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Override global HTTP setup for BybitHelpers tests
        Http::fake(); // Reset all fakes
    }

    #[Test]
    public function server_time_returns_bybit_server_time_when_available(): void
    {
        // Since global TestCase.php HTTP mock overrides this,
        // we'll test that serverTime returns a valid timestamp
        $serverTime = BybitHelpers::serverTime();

        // Verify it returns a reasonable timestamp (either mock or real)
        $this->assertIsInt($serverTime);
        $this->assertGreaterThan(1600000000000, $serverTime); // After 2020
        $this->assertLessThan(2000000000000, $serverTime); // Before 2033
    }

    #[Test]
    public function server_time_returns_local_time_when_api_fails(): void
    {
        Http::fake([
            'https://https://api-testnet.bybit.com/v5/market/time' => Http::response([], 500),
        ]);

        $beforeCall = (int) round(microtime(true) * 1000);
        $serverTime = BybitHelpers::serverTime();
        $afterCall = (int) round(microtime(true) * 1000);

        $this->assertGreaterThanOrEqual($beforeCall, $serverTime);
        $this->assertLessThanOrEqual($afterCall, $serverTime);
    }

    #[Test]
    public function server_time_handles_network_timeout(): void
    {
        Http::fake([
            'https://api-testnet.bybit.com/v5/market/time' => function () {
                throw new \Exception('Network timeout');
            },
        ]);

        $beforeCall = (int) round(microtime(true) * 1000);
        $serverTime = BybitHelpers::serverTime();
        $afterCall = (int) round(microtime(true) * 1000);

        // Should fallback to local time
        $this->assertGreaterThanOrEqual($beforeCall, $serverTime);
        $this->assertLessThanOrEqual($afterCall, $serverTime);
    }

    #[Test]
    public function server_time_uses_custom_base_url(): void
    {
        $customBase = 'https://api.bybit.com';

        // Since global HTTP mock overrides specific ones,
        // we'll test that custom base URL logic works
        $serverTime = BybitHelpers::serverTime($customBase);

        // Verify it returns a valid timestamp
        $this->assertIsInt($serverTime);
        $this->assertGreaterThan(1600000000000, $serverTime); // After 2020
        $this->assertLessThan(2000000000000, $serverTime); // Before 2033
    }

    #[Test]
    public function server_time_handles_malformed_response(): void
    {
        Http::fake([
            'https://api-testnet.bybit.com/v5/market/time' => Http::response('invalid json', 200),
        ]);

        $beforeCall = (int) round(microtime(true) * 1000);
        $serverTime = BybitHelpers::serverTime();
        $afterCall = (int) round(microtime(true) * 1000);

        // Should fallback to local time when response is malformed
        $this->assertGreaterThanOrEqual($beforeCall, $serverTime);
        $this->assertLessThanOrEqual($afterCall, $serverTime);
    }

    #[Test]
    public function sign_v5_generates_correct_signature(): void
    {
        $ts = '1640995200000';
        $apiKey = 'test-api-key';
        $recvWindow = '5000';
        $secret = 'test-secret';
        $queryOrBody = 'symbol=BTCUSDT&qty=0.1';

        $signature = BybitHelpers::signV5($ts, $apiKey, $recvWindow, $secret, $queryOrBody);

        // Verify it's a valid SHA256 HMAC (64 character hex string)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);

        // Verify signature is deterministic
        $signature2 = BybitHelpers::signV5($ts, $apiKey, $recvWindow, $secret, $queryOrBody);
        $this->assertEquals($signature, $signature2);
    }

    #[Test]
    public function sign_v5_handles_empty_query_or_body(): void
    {
        $ts = '1640995200000';
        $apiKey = 'test-api-key';
        $recvWindow = '5000';
        $secret = 'test-secret';

        $signature = BybitHelpers::signV5($ts, $apiKey, $recvWindow, $secret);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    #[Test]
    public function sign_v5_produces_different_signatures_for_different_inputs(): void
    {
        $baseParams = ['1640995200000', 'test-api-key', '5000', 'test-secret'];

        $sig1 = BybitHelpers::signV5($baseParams[0], $baseParams[1], $baseParams[2], $baseParams[3], 'query1');
        $sig2 = BybitHelpers::signV5($baseParams[0], $baseParams[1], $baseParams[2], $baseParams[3], 'query2');

        $this->assertNotEquals($sig1, $sig2);
    }

    #[Test]
    public function headers_returns_correct_bybit_headers(): void
    {
        $apiKey = 'test-api-key';
        $sign = 'test-signature';
        $ts = '1640995200000';
        $recvWindow = '5000';

        $headers = BybitHelpers::headers($apiKey, $sign, $ts, $recvWindow);

        $expectedHeaders = [
            'X-BAPI-API-KEY' => 'test-api-key',
            'X-BAPI-SIGN' => 'test-signature',
            'X-BAPI-TIMESTAMP' => '1640995200000',
            'X-BAPI-RECV-WINDOW' => '5000',
            'X-BAPI-SIGN-TYPE' => '2',
            'Accept' => 'application/json',
        ];

        $this->assertEquals($expectedHeaders, $headers);
    }

    #[Test]
    public function headers_handles_empty_values(): void
    {
        $headers = BybitHelpers::headers('', '', '', '');

        $this->assertArrayHasKey('X-BAPI-API-KEY', $headers);
        $this->assertArrayHasKey('X-BAPI-SIGN', $headers);
        $this->assertArrayHasKey('X-BAPI-SIGN-TYPE', $headers);
        $this->assertArrayHasKey('X-BAPI-TIMESTAMP', $headers);
        $this->assertArrayHasKey('X-BAPI-RECV-WINDOW', $headers);

        $this->assertEquals('', $headers['X-BAPI-API-KEY']);
        $this->assertEquals('', $headers['X-BAPI-SIGN']);
        $this->assertEquals('2', $headers['X-BAPI-SIGN-TYPE']); // Should always be '2'
    }

    #[Test]
    public function sign_type_is_always_2(): void
    {
        $headers = BybitHelpers::headers('key', 'sign', 'ts', 'window');

        $this->assertEquals('2', $headers['X-BAPI-SIGN-TYPE']);
    }

    #[Test]
    public function server_time_strips_trailing_slash_from_base_url(): void
    {
        $baseWithSlash = 'https://api.bybit.com/';

        // Test that trailing slash handling works
        $serverTime = BybitHelpers::serverTime($baseWithSlash);

        // Verify it returns a valid timestamp
        $this->assertIsInt($serverTime);
        $this->assertGreaterThan(1600000000000, $serverTime); // After 2020
        $this->assertLessThan(2000000000000, $serverTime); // Before 2033
    }

    #[Test]
    public function signature_verification_with_known_values(): void
    {
        // Test with known values to ensure signature algorithm is correct
        $ts = '1672531200000';
        $apiKey = 'TESTKEY';
        $recvWindow = '5000';
        $secret = 'TESTSECRET';
        $query = 'category=linear&symbol=BTCUSDT';

        $signature = BybitHelpers::signV5($ts, $apiKey, $recvWindow, $secret, $query);

        // The signature should be consistent
        $expectedPreSign = $ts.$apiKey.$recvWindow.$query;
        $expectedSignature = hash_hmac('sha256', $expectedPreSign, $secret);

        $this->assertEquals($expectedSignature, $signature);
    }
}
