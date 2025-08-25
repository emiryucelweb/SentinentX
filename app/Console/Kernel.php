<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

final class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sentx:lab-scan')->everyTwoHours()->withoutOverlapping();
        $schedule->command('sentx:eod-metrics')->dailyAt('23:59')->withoutOverlapping();
        $schedule->command('sentx:reconcile-positions')->everyTenMinutes()->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
