<?php

namespace App\Console\Commands\Mongo;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\ListAnimeController;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronLIstAnimeGenerateByAlfabet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronLIstAnimeGenerateByAlfabet:CronLIstAnimeGenerateByAlfabetV1 {start_by_index} {end_by_index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronLIstAnimeGenerate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->ListAnimeController = new ListAnimeController();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $startByIndex = $this->argument('start_by_index');
        $EndByIndex = $this->argument('end_by_index');

        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/CronLIstAnimeGenerateByAlfabet.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $ListAnime = [
            'params' => [
                'start_name_index' => $startByIndex,
                'end_name_index' => $EndByIndex,
                'show_log' => TRUE
            ]
        ];
        try{
            $data = $this->ListAnimeController->ListAnimeGenerate(NULL,$ListAnime);
            echo json_encode($data)."\n\n";
        }catch(\Exception $e){
            $status = 'Not Complete';
        }
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}