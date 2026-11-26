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
        // Weekly backup (Friday 2:00 AM by default): database + uploads → encrypted ZIP → Google Drive.
        $days = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $schedule->command('backup:run')
            ->weeklyOn($days[strtolower(config('backup.day', 'friday'))] ?? 5, config('backup.time', '02:00'))
            ->timezone('Asia/Manila')
            ->withoutOverlapping(120);

        // Proof that the server's cron job is calling the scheduler (shown on Settings → Backups).
        $schedule->call(fn () => @touch(storage_path('framework/scheduler-heartbeat')))->everyFiveMinutes();
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
