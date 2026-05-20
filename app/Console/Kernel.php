<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('weather:fetch')->cron('0 */1 * * *'); // every 3 hours
        $schedule->command('soilmoisture:fetch')->dailyAt('11:30')->appendOutputTo(storage_path('logs/soilmoisture_cron.log'));
        $schedule->command('ndvi:fetch')->dailyAt('04:00')->appendOutputTo(storage_path('logs/ndvi_cron.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
