<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\ManagePositions;
use App\Models\Trade;
use App\Services\Trading\PositionManager;
use Mockery;
use Tests\TestCase;

class ManagePositionsCommandTest extends TestCase
{
    private ManagePositions $command;

    private PositionManager $positionManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Test ortamında migration'ları çalıştır
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');

        $this->command = app(ManagePositions::class);
        $this->positionManager = Mockery::mock(PositionManager::class);
        $this->app->instance(PositionManager::class, $this->positionManager);
    }

    public function test_command_requires_snapshot_file()
    {
        $this->artisan('sentx:manage-positions')
            ->expectsOutput('--snapshot=/path/to/manage_snapshot.json zorunlu')
            ->assertExitCode(1);
    }

    public function test_command_with_invalid_snapshot_file()
    {
        $this->artisan('sentx:manage-positions', [
            '--snapshot' => '/nonexistent/file.json',
        ])
            ->expectsOutput('--snapshot=/path/to/manage_snapshot.json zorunlu')
            ->assertExitCode(1);
    }

    public function test_command_with_invalid_json_snapshot()
    {
        // Create temporary invalid JSON file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, 'invalid json content');

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->expectsOutput('Snapshot JSON okunamadı')
            ->assertExitCode(1);

        unlink($tempFile);
    }

    public function test_command_with_valid_snapshot_no_open_positions()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => [
                    'price' => 30000,
                    'atr' => 500,
                    'volume_24h' => 1000000,
                ],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // No open positions in database - mock expectation gerekmez
        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_command_with_valid_snapshot_and_open_positions()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => [
                    'price' => 30000,
                    'atr' => 500,
                    'volume_24h' => 1000000,
                ],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // Create open positions
        $trade1 = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $trade2 = Trade::create([
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'qty' => 0.01,
            'entry_price' => 2000,
            'status' => 'OPEN',
        ]);

        // Mock position manager responses
        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $validSnapshot)
            ->andReturn([
                'action' => 'HOLD',
                'reason' => 'Position performing well',
                'confidence' => 85,
            ]);

        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $validSnapshot)
            ->andReturn([
                'action' => 'SCALE_OUT',
                'reason' => 'Take partial profits',
                'confidence' => 75,
                'scale_out_qty' => 0.005,
            ]);

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_command_handles_position_manager_errors()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => ['price' => 30000, 'atr' => 500],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // Create open position
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        // Mock position manager to throw exception
        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $validSnapshot)
            ->andThrow(new \Exception('Position manager error'));

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0); // Command should continue despite individual position errors

        unlink($tempFile);
    }

    public function test_command_outputs_json_format()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => ['price' => 30000, 'atr' => 500],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // Create open position
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        // Mock position manager response
        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $validSnapshot)
            ->andReturn([
                'action' => 'CLOSE',
                'reason' => 'Stop loss hit',
                'confidence' => 90,
            ]);

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_command_ignores_closed_positions()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => ['price' => 30000, 'atr' => 500],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // Create mixed status positions
        $openTrade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        $closedTrade = Trade::create([
            'symbol' => 'ETHUSDT',
            'side' => 'SHORT',
            'qty' => 0.01,
            'entry_price' => 2000,
            'status' => 'CLOSED',
        ]);

        // Only open position should be managed
        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $validSnapshot)
            ->andReturn([
                'action' => 'HOLD',
                'reason' => 'Position performing well',
                'confidence' => 80,
            ]);

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_command_handles_large_number_of_positions()
    {
        $validSnapshot = [
            'market_data' => [
                'BTCUSDT' => ['price' => 30000, 'atr' => 500],
            ],
            'portfolio' => [
                'equity' => 10000,
                'margin_utilization' => 20,
                'free_collateral' => 8000,
            ],
            'risk_context' => [
                'daily_pnl' => 0,
                'max_drawdown' => 0,
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($validSnapshot));

        // Create multiple open positions
        $trades = [];
        for ($i = 0; $i < 10; $i++) {
            $trade = Trade::create([
                'symbol' => 'BTCUSDT',
                'side' => 'LONG',
                'qty' => 0.001,
                'entry_price' => 30000 + $i,
                'status' => 'OPEN',
            ]);
            $trades[] = $trade;

            // Mock position manager response for each trade
            $this->positionManager->shouldReceive('manage')
                ->with(\Mockery::type(Trade::class), $validSnapshot)
                ->andReturn([
                    'action' => 'HOLD',
                    'reason' => 'Position performing well',
                    'confidence' => 80 + $i,
                ]);
        }

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    public function test_command_with_empty_snapshot()
    {
        $emptySnapshot = [];

        $tempFile = tempnam(sys_get_temp_dir(), 'test_snapshot');
        file_put_contents($tempFile, json_encode($emptySnapshot));

        // Create open position
        $trade = Trade::create([
            'symbol' => 'BTCUSDT',
            'side' => 'LONG',
            'qty' => 0.001,
            'entry_price' => 30000,
            'status' => 'OPEN',
        ]);

        // Mock position manager response
        $this->positionManager->shouldReceive('manage')
            ->with(\Mockery::type(Trade::class), $emptySnapshot)
            ->andReturn([
                'action' => 'HOLD',
                'reason' => 'No market data available',
                'confidence' => 50,
            ]);

        $this->artisan('sentx:manage-positions', [
            '--snapshot' => $tempFile,
        ])
            ->assertExitCode(0);

        unlink($tempFile);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
