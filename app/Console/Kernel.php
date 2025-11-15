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
        // Example scheduled tasks
        // $schedule->command('inspire')->hourly();
        
        // Clean up old logs (if you have a log:clear command)
        // $schedule->command('log:clear')->daily();
        
        // Send scheduled notifications
        // $schedule->command('notifications:send')->everyMinute();
        
        // Backup database (if using backup package)
        // $schedule->command('backup:run')->daily()->at('02:00');
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


