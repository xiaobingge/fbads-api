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
        // 昨天的
        $schedule->command('sync:adcampaigns', ['--yesterday'])->dailyAt('05:17');
        $schedule->command('sync:adsets', ['--yesterday'])->dailyAt('05:17');
        $schedule->command('sync:ads', ['--yesterday'])->dailyAt('05:17');
        $schedule->command('sync:ad-insights-account', ['--yesterday'])->dailyAt('05:57');
        $schedule->command('sync:ad-campaign-insights', ['--yesterday'])->dailyAt('05:57');
        $schedule->command('sync:ad-set-insights', ['--yesterday'])->dailyAt('05:57');
        $schedule->command('sync:ad-insights', ['--yesterday'])->dailyAt('05:57');

        // 今天的
        $schedule->command('sync:adcampaigns')->cron('5 1 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:adsets')->cron('5 11 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ads')->cron('5 21 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:pages')->hourlyAt(17)->withoutOverlapping();

        $schedule->command('sync:ad-insights-account')->cron('5 31 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-campaign-insights')->cron('5 31 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-set-insights')->cron('5 35 6/4 * * ? *')->withoutOverlapping();
        $schedule->command('sync:ad-insights')->cron('5 39 6/4 * * ? *')->withoutOverlapping();
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
