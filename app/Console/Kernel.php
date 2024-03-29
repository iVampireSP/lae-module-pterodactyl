<?php

namespace App\Console;

use App\Jobs\RefreshNestJob;
use App\Http\Controllers\JobController;
use App\Jobs\CheckNode;
use App\Jobs\RefreshHostJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            dispatch(new RefreshNestJob());
        })->hourly()->name('Wings');

        $schedule->job(new CheckNode())->everyMinute()->name('CheckNode');
        $schedule->job(new RefreshHostJob())->everyTenMinutes()->name('RefreshHostJob');

        // run recount command every 5 minutes
        $schedule->command('recount')->everyFiveMinutes()->name('Recount location servers');

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
