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
        \App\Console\Commands\AdInsightsAccountCommand::class,
        \App\Console\Commands\AdInsightsAdCommand::class,
        \App\Console\Commands\AdInsightsAdSetCommand::class,
        \App\Console\Commands\AdInsightsCampaignCommand::class,
        \App\Console\Commands\SyncAdCampaignsCommand::class,
        \App\Console\Commands\SyncAdsCommand::class,
        \App\Console\Commands\SyncAdSetsCommand::class,
        \App\Console\Commands\SyncPagesCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        $schedule->command('sync:adcampaigns')->cron('5 1 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:adsets')->cron('5 11 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ads')->cron('5 21 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:pages')->hourlyAt(17)->withoutOverlapping();

        $schedule->command('sync:ad-insights-account')->cron('5 31 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-campaign-insights')->cron('5 31 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-set-insights')->cron('5 35 5/2 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-insights')->cron('5 39 5/2 * * ? *')->withoutOverlapping();
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
