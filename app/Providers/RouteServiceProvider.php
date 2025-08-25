<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            $api = base_path('routes/api.php');
            if (file_exists($api)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group($api);
            }

            $web = base_path('routes/web.php');
            if (file_exists($web)) {
                Route::middleware('web')
                    ->group($web);
            }
        });
    }
}
