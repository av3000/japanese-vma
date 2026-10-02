<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Terminal-state guarantee for article processing (issue #245): a job killed by a
        // timeout, OOM or worker restart cannot update its processing_states row itself.
        $schedule->command('article-processing:sweep-stale')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Content Import (epic #404): one run a day, in the Japanese morning, after the night's
        // news is out. withoutOverlapping keeps two runs from racing for the same articles.
        $schedule->command('content:import')
            ->dailyAt('07:30')
            ->timezone('Asia/Tokyo')
            ->withoutOverlapping(60);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
