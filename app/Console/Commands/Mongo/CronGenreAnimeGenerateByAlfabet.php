<?php

namespace App\Console\Commands\Mongo;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\GenreListAnimeController;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronGenreAnimeGenerateByAlfabet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronGenreAnimeGenerateByAlfabet:CronGenreAnimeGenerateByAlfabetV1 {start_by_index} {end_by_index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronGenreAnimeGenerateV1';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->GenreListAnimeController = new GenreListAnimeController();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        // $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);
        $startByIndex = $this->argument('start_by_index');
        $EndByIndex = $this->argument('end_by_index');

        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/CronGenreAnimeGenerateByAlfabetV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $i = 0;
        $dataNotSave = array();
        $GenreListAnime = [
            'params' => [
                'start_name_index' => $startByIndex,
                'end_name_index' => $EndByIndex,
                'show_log' => TRUE
            ]
        ];
        try{
            $data = $this->GenreListAnimeController->generateGenreListAnime(NULL,$GenreListAnime);
            echo json_encode($data)."\n\n";
        }catch(\Exception $e){
            $status = 'Not Complete';
        }
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}