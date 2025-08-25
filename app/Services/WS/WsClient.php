<?php

declare(strict_types=1);

namespace App\Services\WS;

use App\Contracts\Exchange\ExchangeClientInterface;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;

final class WsClient
{
    private ?WebSocket $connection = null;

    private array $subscriptions = [];

    private array $messageQueue = [];

    private bool $isConnected = false;

    private int $reconnectAttempts = 0;

    private const MAX_RECONNECT_ATTEMPTS = 5;

    public function __construct(
        private readonly ExchangeClientInterface $exchange,
        private readonly LoopInterface $loop,
        private readonly string $wsUrl = 'wss://stream.bybit.com/v5/public/linear',
        private readonly int $pingInterval = 30,
        private readonly int $pongTimeout = 10
    ) {}

    /**
     * WebSocket bağlantısını kur
     */
    public function connect(): bool
    {
        try {
            $connector = new Connector($this->loop);

            $connector($this->wsUrl)->then(
                function (WebSocket $conn) {
                    $this->connection = $conn;
                    $this->isConnected = true;
                    $this->reconnectAttempts = 0;

                    $this->setupEventHandlers($conn);
                    $this->startPingTimer();

                    Log::info('WebSocket connected', ['url' => $this->wsUrl]);
                },
                function (\Exception $e) {
                    Log::error('WebSocket connection failed', [
                        'url' => $this->wsUrl,
                        'error' => $e->getMessage(),
                    ]);
                    $this->handleReconnect();
                }
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('WebSocket connection error', [
                'url' => $this->wsUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Event handler'ları kur
     */
    private function setupEventHandlers(WebSocket $conn): void
    {
        $conn->on('message', function ($msg) {
            $this->handleMessage($msg);
        });

        $conn->on('close', function ($code = null, $reason = null) {
            $this->isConnected = false;
            Log::warning('WebSocket connection closed', [
                'code' => $code,
                'reason' => $reason,
            ]);
            $this->handleReconnect();
        });

        $conn->on('error', function (\Exception $e) {
            Log::error('WebSocket error', ['error' => $e->getMessage()]);
            $this->isConnected = false;
        });
    }

    /**
     * Mesaj işleme
     */
    private function handleMessage($msg): void
    {
        try {
            $data = json_decode($msg, true);

            if (! $data) {
                Log::warning('Invalid WebSocket message format', ['message' => $msg]);

                return;
            }

            // Ping/Pong handling
            if (isset($data['op']) && $data['op'] === 'pong') {
                $this->handlePong($data);

                return;
            }

            // Market data handling
            if (isset($data['topic']) && str_contains($data['topic'], 'orderbook')) {
                $this->handleOrderBookUpdate($data);
            } elseif (isset($data['topic']) && str_contains($data['topic'], 'trade')) {
                $this->handleTradeUpdate($data);
            } elseif (isset($data['topic']) && str_contains($data['topic'], 'ticker')) {
                $this->handleTickerUpdate($data);
            }

        } catch (\Throwable $e) {
            Log::error('WebSocket message handling error', [
                'message' => $msg,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * OrderBook güncellemesi
     */
    private function handleOrderBookUpdate(array $data): void
    {
        // Gap detection
        $this->detectGaps($data);

        // Cache güncelle
        $this->updateOrderBookCache($data);
    }

    /**
     * Trade güncellemesi
     */
    private function handleTradeUpdate(array $data): void
    {
        // Gap detection
        $this->detectGaps($data);

        // Trade cache güncelle
        $this->updateTradeCache($data);
    }

    /**
     * Ticker güncellemesi
     */
    private function handleTickerUpdate(array $data): void
    {
        // Gap detection
        $this->detectGaps($data);

        // Ticker cache güncelle
        $this->updateTickerCache($data);
    }

    /**
     * Data gap detection
     */
    private function detectGaps(array $data): void
    {
        $symbol = $data['topic'] ?? 'unknown';
        $timestamp = $data['ts'] ?? time() * 1000;

        // Gap detection logic
        $lastTimestamp = cache("ws_last_timestamp_{$symbol}", 0);
        $expectedInterval = 1000; // 1 second for most streams

        if ($lastTimestamp > 0 && ($timestamp - $lastTimestamp) > $expectedInterval * 2) {
            Log::warning('WebSocket data gap detected', [
                'symbol' => $symbol,
                'last_timestamp' => $lastTimestamp,
                'current_timestamp' => $timestamp,
                'gap_ms' => $timestamp - $lastTimestamp,
            ]);

            // Trigger backfill
            $this->triggerBackfill($symbol, $lastTimestamp, $timestamp);
        }

        cache(["ws_last_timestamp_{$symbol}" => $timestamp], now()->addMinutes(5));
    }

    /**
     * Backfill tetikle
     */
    private function triggerBackfill(string $symbol, int $fromTimestamp, int $toTimestamp): void
    {
        // REST API ile eksik veriyi çek
        try {
            $fromTime = (int) ($fromTimestamp / 1000);
            $toTime = (int) ($toTimestamp / 1000);

            // Market data backfill
            $this->exchange->getKlineData($symbol, '1', $fromTime, $toTime);

            Log::info('WebSocket backfill completed', [
                'symbol' => $symbol,
                'from' => $fromTime,
                'to' => $toTime,
            ]);

        } catch (\Throwable $e) {
            Log::error('WebSocket backfill failed', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Ping timer başlat
     */
    private function startPingTimer(): void
    {
        $this->loop->addPeriodicTimer($this->pingInterval, function () {
            if ($this->isConnected && $this->connection) {
                $this->sendPing();
            }
        });
    }

    /**
     * Ping gönder
     */
    private function sendPing(): void
    {
        if ($this->connection) {
            $pingData = json_encode(['op' => 'ping']);
            $this->connection->send($pingData);

            // Pong timeout timer
            $this->loop->addTimer($this->pongTimeout, function () {
                if ($this->isConnected) {
                    Log::warning('Pong timeout - reconnecting');
                    $this->reconnect();
                }
            });
        }
    }

    /**
     * Pong işle
     */
    private function handlePong(array $data): void
    {
        // Pong received, connection is healthy
        Log::debug('WebSocket pong received', ['data' => $data]);
    }

    /**
     * Yeniden bağlanma
     */
    private function handleReconnect(): void
    {
        if ($this->reconnectAttempts < self::MAX_RECONNECT_ATTEMPTS) {
            $this->reconnectAttempts++;
            $delay = min(30, pow(2, $this->reconnectAttempts)); // Exponential backoff

            Log::info('WebSocket reconnecting', [
                'attempt' => $this->reconnectAttempts,
                'delay' => $delay,
            ]);

            $this->loop->addTimer($delay, function () {
                $this->connect();
            });
        } else {
            Log::error('WebSocket max reconnection attempts reached');
        }
    }

    /**
     * Yeniden bağlanma
     */
    public function reconnect(): void
    {
        $this->isConnected = false;
        if ($this->connection) {
            $this->connection->close();
        }
        $this->connect();
    }

    /**
     * Bağlantı durumu
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * Bağlantıyı kapat
     */
    public function disconnect(): void
    {
        $this->isConnected = false;
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * OrderBook cache güncelle
     */
    private function updateOrderBookCache(array $data): void
    {
        // Implementation for orderbook cache update
    }

    /**
     * Trade cache güncelle
     */
    private function updateTradeCache(array $data): void
    {
        // Implementation for trade cache update
    }

    /**
     * Ticker cache güncelle
     */
    private function updateTickerCache(array $data): void
    {
        // Implementation for ticker cache update
    }
}
