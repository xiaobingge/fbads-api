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
        \App\Console\Commands\FaceGoodCommand::class,
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
        $schedule->command('sync:adcampaigns', ['--yesterday'])->dailyAt('00:17');
        $schedule->command('sync:adsets', ['--yesterday'])->dailyAt('00:17');
        $schedule->command('sync:ads', ['--yesterday'])->dailyAt('00:17');
        $schedule->command('sync:ad-insights-account', ['--yesterday'])->dailyAt('00:57');
        $schedule->command('sync:ad-campaign-insights', ['--yesterday'])->dailyAt('00:57');
        $schedule->command('sync:ad-set-insights', ['--yesterday'])->dailyAt('00:57');
        $schedule->command('sync:ad-insights', ['--yesterday'])->dailyAt('00:57');

        // 今天的
        $schedule->command('sync:adcampaigns')->hourlyAt(11)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:adsets')->hourlyAt(11)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:ads')->hourlyAt(11)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:pages')->hourlyAt(17)->between('8:00', '23:00')->withoutOverlapping();

        $schedule->command('sync:ad-insights-account')->hourlyAt(21)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:ad-campaign-insights')->hourlyAt(31)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:ad-set-insights')->hourlyAt(35)->between('8:00', '23:00')->withoutOverlapping();
        $schedule->command('sync:ad-insights')->hourlyAt(41)->between('8:00', '23:00')->withoutOverlapping();
        //采集商品
        $schedule->command('faceGood:cai')->everyMinute()->withoutOverlapping();

        //推送商品
        $schedule->command('push:goods', [202])->everyFiveMinutes()->withoutOverlapping();
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
