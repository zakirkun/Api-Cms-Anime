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
        '\App\Console\Commands\Mysql\CronLIstAnimeGenerate',
        '\App\Console\Commands\Mysql\CronDetailListAnimeGenerateByAlfabet',
        '\App\Console\Commands\Mysql\CronDetailListAnimeGenerateByDate',
        '\App\Console\Commands\Mysql\CronLastUpdateGenerate',
        '\App\Console\Commands\Mysql\CronStreamAnimeGenerateByDate',
        '\App\Console\Commands\Mysql\CronStreamAnimeGenerateByID',
        '\App\Console\Commands\Mysql\CronTrendingweekGenerate',
        '\App\Console\Commands\Mysql\CronGenreAnimeGenerate',
        '\App\Console\Commands\Mysql\CronScheduleAnimeGenerate',
        
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
        if($config['Cron_ListAnimeGenerate']) {
            $schedule->command('CronLIstAnimeGenerate:CronLIstAnimeGenerateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_ListAnimeGenerate.log');
        }

        if($config['Cron_DetailListAnimeGenerateByAlfabet']) {
            $schedule->command('CronDetailListAnimeGenerateByAlfabet:DetailListAnimeGenerateByAlfabetV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_DetailListAnimeGenerateByAlfabet.log');
        }

        if($config['Cron_DetailListAnimeGenerateByDate']) {
            $schedule->command('CronDetailListAnimeGenerateByDate:DetailListAnimeGenerateByDateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_DetailListAnimeGenerateByDate.log');
        }

        if($config['Cron_LastUpdate_Generate']) {
            $schedule->command('CronLastUpdateGenerate:CronLastUpdateGenerateV1 ')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_LastUpdate_Generate.log');
        }

        if($config['Cron_StreamAnime_GenerateByDate']) {
            $schedule->command('CronStreamAnimeGenerateByDate:CronStreamAnimeGenerateByDateV1 ')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_StreamAnime_GenerateByDate.log');
        }

        if($config['Cron_StreamAnime_GenerateByID']) {
            $schedule->command('CronStreamAnimeGenerateByID:CronStreamAnimeGenerateByIDV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_StreamAnime_GenerateByID.log');
        }

        if($config['Cron_Trendingweek_Generate']) {
            $schedule->command('CronTrendingweekGenerate:CronTrendingweekGenerateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_Trendingweek_Generate.log');
        }

        if($config['Cron_GenreAnime_Generate']) {
            $schedule->command('CronGenreAnimeGenerate:CronGenreAnimeGenerateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_GenreAnime_Generate.log');
        }

        if($config['Cron_ScheduleAnime_Generate']) {
            $schedule->command('CronScheduleAnimeGenerate:CronScheduleAnimeGenerateV1')
                ->everyMinute() #setiap menit
                ->appendOutputTo('/tmp/Cron_ScheduleAnime_Generate.log');
        }

    }
   
}


