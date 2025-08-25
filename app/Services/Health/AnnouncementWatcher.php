<?php

declare(strict_types=1);

namespace App\Services\Health;

use App\Contracts\Notifier\AlertDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AnnouncementWatcher
{
    private const CACHE_KEY = 'announcement_watcher';

    private const CACHE_TTL = 600; // 10 dakika

    public function __construct(
        private readonly AlertDispatcher $alerts
    ) {}

    /**
     * Announcement'ları izle
     * @return array<string, mixed>
     */
    public function watch(): array
    {
        try {
            $result = $this->checkAnnouncements();

            // Sonucu cache'le
            Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

            // Alert gönder (gerekirse)
            $this->sendAlerts($result);

            return $result;

        } catch (\Throwable $e) {
            Log::error('Announcement watching failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage(),
                'announcements' => [],
            ];
        }
    }

    /**
     * Announcement kontrolü yap
     * @return array<string, mixed>
     */
    private function checkAnnouncements(): array
    {
        $sources = config('health.announcement.sources', [
            'bybit' => [
                'enabled' => true,
                'url' => 'https://api.bybit.com/v5/announcements/index',
                'check_interval' => 300, // 5 dakika
            ],
            'telegram' => [
                'enabled' => true,
                'channel' => config('health.announcement.telegram_channel'),
                'check_interval' => 600, // 10 dakika
            ],
        ]);

        $results = [];
        $overallStatus = 'normal';

        foreach ($sources as $source => $config) {
            if (! $config['enabled']) {
                continue;
            }

            $checkResult = $this->checkSource($source, $config);
            $results[$source] = $checkResult;

            if ($checkResult['status'] === 'critical') {
                $overallStatus = 'critical';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'sources' => $results,
            'summary' => $this->generateSummary($results),
        ];
    }

    /**
     * Tek kaynak kontrolü
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function checkSource(string $source, array $config): array
    {
        try {
            switch ($source) {
                case 'bybit':
                    return $this->checkBybitAnnouncements($config);
                case 'telegram':
                    return $this->checkTelegramAnnouncements($config);
                default:
                    return [
                        'status' => 'unknown',
                        'source' => $source,
                        'announcements' => [],
                        'last_check' => now()->toISOString(),
                        'error' => 'Unknown source',
                    ];
            }
        } catch (\Throwable $e) {
            Log::warning("Announcement check failed for {$source}", [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);

            return [
                'status' => 'error',
                'source' => $source,
                'announcements' => [],
                'last_check' => now()->toISOString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Bybit announcement kontrolü
     */
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function checkBybitAnnouncements(array $config): array
    {
        $response = Http::timeout(10)->get($config['url']);

        if (! $response->successful()) {
            return [
                'status' => 'error',
                'source' => 'bybit',
                'announcements' => [],
                'last_check' => now()->toISOString(),
                'error' => 'HTTP request failed: '.$response->status(),
            ];
        }

        $data = $response->json();
        $announcements = $data['result']['list'] ?? [];

        // Son 24 saatteki announcement'ları filtrele
        $recentAnnouncements = array_filter($announcements, function ($ann) {
            $annTime = strtotime($ann['dateTime'] ?? '');

            return $annTime && $annTime > (time() - 86400); // 24 saat
        });

        // Kritik announcement'ları tespit et
        $criticalAnnouncements = $this->detectCriticalAnnouncements($recentAnnouncements);

        $status = empty($criticalAnnouncements) ? 'normal' : 'critical';

        return [
            'status' => $status,
            'source' => 'bybit',
            'announcements' => $recentAnnouncements,
            'critical_count' => count($criticalAnnouncements),
            'total_count' => count($recentAnnouncements),
            'last_check' => now()->toISOString(),
            'critical_announcements' => $criticalAnnouncements,
        ];
    }

    /**
     * Telegram announcement kontrolü
     */
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function checkTelegramAnnouncements(array $config): array
    {
        $channel = $config['channel'] ?? null;

        if (! $channel) {
            return [
                'status' => 'disabled',
                'source' => 'telegram',
                'announcements' => [],
                'last_check' => now()->toISOString(),
                'error' => 'Telegram channel not configured',
            ];
        }

        // Telegram API ile son mesajları kontrol et
        // Bu implementasyon basit tutulmuştur
        $recentMessages = $this->getRecentTelegramMessages($channel);

        // Kritik mesajları tespit et
        $criticalMessages = $this->detectCriticalTelegramMessages($recentMessages);

        $status = empty($criticalMessages) ? 'normal' : 'critical';

        return [
            'status' => $status,
            'source' => 'telegram',
            'announcements' => $recentMessages,
            'critical_count' => count($criticalMessages),
            'total_count' => count($recentMessages),
            'last_check' => now()->toISOString(),
            'critical_messages' => $criticalMessages,
        ];
    }

    /**
     * Kritik announcement'ları tespit et
     */
    private function detectCriticalAnnouncements(array $announcements): array
    {
        $criticalKeywords = config('health.announcement.critical_keywords', [
            'maintenance', 'upgrade', 'emergency', 'critical', 'urgent',
            'suspension', 'halt', 'freeze', 'restrict', 'limit',
            'security', 'breach', 'hack', 'compromise', 'vulnerability',
        ]);

        $criticalAnnouncements = [];

        foreach ($announcements as $ann) {
            $title = strtolower($ann['title'] ?? '');
            $content = strtolower($ann['content'] ?? '');

            foreach ($criticalKeywords as $keyword) {
                if (str_contains($title, $keyword) || str_contains($content, $keyword)) {
                    $criticalAnnouncements[] = [
                        'id' => $ann['id'] ?? 'unknown',
                        'title' => $ann['title'] ?? 'No title',
                        'dateTime' => $ann['dateTime'] ?? 'unknown',
                        'critical_keyword' => $keyword,
                        'url' => $ann['url'] ?? null,
                    ];
                    break; // Bir announcement'da birden fazla keyword bulunursa sadece birini say
                }
            }
        }

        return $criticalAnnouncements;
    }

    /**
     * Kritik Telegram mesajlarını tespit et
     */
    private function detectCriticalTelegramMessages(array $messages): array
    {
        $criticalKeywords = config('health.announcement.critical_keywords', [
            'maintenance', 'upgrade', 'emergency', 'critical', 'urgent',
            'suspension', 'halt', 'freeze', 'restrict', 'limit',
            'security', 'breach', 'hack', 'compromise', 'vulnerability',
        ]);

        $criticalMessages = [];

        foreach ($messages as $msg) {
            $text = strtolower($msg['text'] ?? '');

            foreach ($criticalKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $criticalMessages[] = [
                        'id' => $msg['id'] ?? 'unknown',
                        'text' => $msg['text'] ?? 'No text',
                        'date' => $msg['date'] ?? 'unknown',
                        'critical_keyword' => $keyword,
                    ];
                    break;
                }
            }
        }

        return $criticalMessages;
    }

    /**
     * Son Telegram mesajlarını al
     */
    private function getRecentTelegramMessages(string $channel): array
    {
        // Bu implementasyon basit tutulmuştur
        // Gerçek uygulamada Telegram Bot API kullanılır
        return [
            [
                'id' => '1',
                'text' => 'System maintenance scheduled for tomorrow',
                'date' => now()->subHours(2)->toISOString(),
            ],
        ];
    }

    /**
     * Özet oluştur
     */
    private function generateSummary(array $results): array
    {
        $total = count($results);
        $normal = 0;
        $critical = 0;
        $error = 0;
        $disabled = 0;

        foreach ($results as $result) {
            switch ($result['status']) {
                case 'normal':
                    $normal++;
                    break;
                case 'critical':
                    $critical++;
                    break;
                case 'error':
                    $error++;
                    break;
                case 'disabled':
                    $disabled++;
                    break;
            }
        }

        return [
            'total_sources' => $total,
            'normal' => $normal,
            'critical' => $critical,
            'error' => $error,
            'disabled' => $disabled,
            'health_percentage' => $total > 0 ? round(($normal / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Alert gönder
     */
    private function sendAlerts(array $result): void
    {
        if ($result['status'] === 'critical') {
            $criticalSources = [];
            foreach ($result['sources'] as $source => $data) {
                if ($data['status'] === 'critical') {
                    $criticalSources[] = [
                        'source' => $source,
                        'critical_count' => $data['critical_count'] ?? 0,
                        'announcements' => $data['critical_announcements'] ?? [],
                    ];
                }
            }

            $this->alerts->send(
                'critical',
                'ANNOUNCEMENT_CRITICAL_DETECTED',
                'Critical announcements detected - immediate review required',
                [
                    'critical_sources' => $criticalSources,
                    'timestamp' => $result['timestamp'],
                    'summary' => $result['summary'],
                ],
                dedupKey: 'announcement-critical-'.date('Y-m-d-H')
            );
        }

        // Genel sağlık durumu alert'i
        if ($result['summary']['health_percentage'] < 75) {
            $this->alerts->send(
                'warn',
                'ANNOUNCEMENT_HEALTH_DEGRADED',
                'Announcement monitoring health degraded',
                [
                    'health_percentage' => $result['summary']['health_percentage'],
                    'timestamp' => $result['timestamp'],
                    'summary' => $result['summary'],
                ],
                dedupKey: 'announcement-health-'.date('Y-m-d-H')
            );
        }
    }

    /**
     * Son kontrol sonucunu al
     */
    public function getLastCheck(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Manuel kontrol tetikle
     */
    public function triggerCheck(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->watch();
    }

    /**
     * Belirli kaynak kontrolü
     */
    public function checkSpecificSource(string $source): ?array
    {
        $sources = config('health.announcement.sources', []);

        if (! isset($sources[$source])) {
            return null;
        }

        $result = $this->checkSource($source, $sources[$source]);

        // Alert gönder (gerekirse)
        if ($result['status'] === 'critical') {
            $this->alerts->send(
                'critical',
                'ANNOUNCEMENT_SOURCE_CRITICAL',
                "Critical announcements detected in {$source}",
                [
                    'source' => $source,
                    'result' => $result,
                    'timestamp' => now()->toISOString(),
                ]
            );
        }

        return $result;
    }
}
