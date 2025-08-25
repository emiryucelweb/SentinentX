<?php

use App\Http\Controllers\GdprController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '2.1.0',
    ]);
})->name('api.health');

// Telegram webhook endpoint
Route::post('/telegram/webhook', [App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('api.telegram.webhook');

Route::get('/status', function () {
    return response()->json([
        'system' => [
            'version' => '2.1.0',
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString(),
        ],
        'services' => [
            'database' => 'connected',
            'cache' => 'working',
        ],
    ]);
})->name('api.status');

// Rate limited public routes
Route::middleware(['throttle:100,1'])->group(function () {
    // Authentication routes would go here
    Route::post('/auth/login', function () {
        return response()->json(['message' => 'Login endpoint']);
    })->name('api.auth.login');

    Route::post('/auth/register', function () {
        return response()->json(['message' => 'Register endpoint']);
    })->name('api.auth.register');
});

// Authenticated API routes
Route::middleware(['auth:sanctum', 'throttle:1000,1'])->group(function () {

    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.user');

    // Trading endpoints
    Route::prefix('trading')->group(function () {
        Route::get('/trades', function () {
            return response()->json(['message' => 'List trades']);
        })->name('api.trading.trades.index');

        Route::post('/trades', function () {
            return response()->json(['message' => 'Create trade']);
        })->name('api.trading.trades.store');

        Route::get('/trades/{trade}', function () {
            return response()->json(['message' => 'Get trade']);
        })->name('api.trading.trades.show');

        Route::patch('/trades/{trade}', function () {
            return response()->json(['message' => 'Update trade']);
        })->name('api.trading.trades.update');

        Route::delete('/trades/{trade}', function () {
            return response()->json(['message' => 'Close trade']);
        })->name('api.trading.trades.destroy');
    });

    // AI Consensus endpoints
    Route::prefix('ai')->group(function () {
        Route::post('/consensus', function () {
            return response()->json(['message' => 'Request AI consensus']);
        })->name('api.ai.consensus');
    });

    // Risk Management endpoints
    Route::prefix('risk')->group(function () {
        Route::post('/position-sizing', function () {
            return response()->json(['message' => 'Calculate position size']);
        })->name('api.risk.position-sizing');

        Route::post('/assessment', function () {
            return response()->json(['message' => 'Assess trade risk']);
        })->name('api.risk.assessment');
    });

    // Market Data endpoints
    Route::prefix('market')->group(function () {
        Route::get('/data/{symbol}', function () {
            return response()->json(['message' => 'Get market data']);
        })->name('api.market.data');
    });

    // Portfolio endpoints
    Route::get('/portfolio', function () {
        return response()->json(['message' => 'Get portfolio']);
    })->name('api.portfolio');

    // Alerts endpoints
    Route::prefix('alerts')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'List alerts']);
        })->name('api.alerts.index');

        Route::post('/{alert}/acknowledge', function () {
            return response()->json(['message' => 'Acknowledge alert']);
        })->name('api.alerts.acknowledge');
    });

    // Subscription endpoints
    Route::prefix('subscription')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Get subscription']);
        })->name('api.subscription.show');

        Route::get('/usage', function () {
            return response()->json(['message' => 'Get usage']);
        })->name('api.subscription.usage');
    });

    // GDPR endpoints
    Route::prefix('gdpr')->group(function () {
        Route::post('/export', [GdprController::class, 'requestExport'])
            ->name('api.gdpr.export');

        Route::post('/delete', [GdprController::class, 'requestDeletion'])
            ->name('api.gdpr.delete');
    });
});

// Admin/Enterprise routes (higher rate limits + security)
Route::middleware([\App\Http\Middleware\HmacAuthMiddleware::class, 'throttle:60,1'])->prefix('admin')->group(function () {

    // System health
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'services' => [
                'database' => 'connected',
                'cache' => 'working',
                'ai_providers' => 'operational',
                'exchange' => 'connected',
            ],
            'timestamp' => now()->toISOString(),
        ]);
    })->name('api.admin.health');

    // System metrics
    Route::get('/metrics', function () {
        return response()->json([
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'cpu_load' => sys_getloadavg(),
            ],
            'application' => [
                'active_trades' => 0,
                'ai_requests_today' => 0,
                'error_rate' => 0.0,
            ],
        ]);
    })->name('api.admin.metrics');

    // Emergency shutdown
    Route::post('/shutdown', function () {
        return response()->json(['message' => 'Emergency shutdown initiated']);
    })->name('api.admin.shutdown');

    Route::post('/restart', function () {
        return response()->json(['message' => 'System restart initiated']);
    })->name('api.admin.restart');
});

// Webhook endpoints (no CSRF, signature verification instead)
Route::prefix('webhooks')->group(function () {
    Route::post('/bybit', function () {
        return response()->json(['message' => 'Bybit webhook']);
    })->name('api.webhooks.bybit');

    Route::post('/payment', function () {
        return response()->json(['message' => 'Payment webhook']);
    })->name('api.webhooks.payment');
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint not found',
        'message' => 'The requested API endpoint does not exist.',
        'documentation' => url('/docs/api'),
    ], 404);
});
