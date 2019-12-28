<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Config;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        '\App\Console\Commands\Mysql\CronDetailListAnimeGenerateByAlfabet',
        '\App\Console\Commands\Mysql\CronTrendingweekGenerate',
        '\App\Console\Commands\Mysql\CronLastUpdateGenerate',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $config = Config::get('cron/cron_config');

        #Cron Nanime
        if($config['Cron_DetailListAnimeGenerate']) {
            $schedule->command('CronDetailListAnimeGenerateByAlfabet:DetailListAnimeGenerateByAlfabetV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_DetailListAnimeGenerateByAlfabet.log');
        }

        if($config['Cron_Trendingweek_Generate']) {
            $schedule->command('CronTrendingweekGenerate:CronTrendingweekGenerateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_Trendingweek_Generate.log');
        }

        if($config['Cron_LastUpdate_Generate']) {
            $schedule->command('CronLastUpdateGenerate:CronLastUpdateGenerateV1 ')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_LastUpdate_Generate.log');
        }

    }
   
}


