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
        // Archiver les comptes bloqués expirés chaque jour à minuit
        $schedule->job(new \App\Jobs\ArchiverComptesBloques)->daily();

        // Désarchiver les comptes actifs archivés chaque jour à 1h du matin
        $schedule->job(new \App\Jobs\DesarchiverComptes)->dailyAt('01:00');
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
