<?php

namespace App\Console\Commands\Mongo;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\DetailListAnimeController;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronDetailListAnimeGenerateByAlfabet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronDetailListAnimeGenerateByAlfabetMG:DetailListAnimeGenerateByAlfabetMGV1  {start_by_index} {end_by_index} {all_list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronDetailListAnimeGenerateByAlfabet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->DetailListAnimeController = new DetailListAnimeController();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $startByIndex = $this->argument('start_by_index');
        $EndByIndex = $this->argument('end_by_index');
        // $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/DetailListAnimeGenerateByAlfabetV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $param = [
            'code' => '',
            'start_by_index' => substr($startByIndex,0,1),
            'end_by_index' => substr($EndByIndex,0,1),
        ];
        $listAnime = MainModel::getDataListAnime($param);
        
        $status = "Complete";
        $i = 0;
        $dataNotSave = array();
        $TotalHit = (count($listAnime));
        foreach($listAnime as $listAnime){
            $listDataAnime = [
                'params' => [
                    'id_list_anime' => $listAnime['id'],
                    'show_log' => TRUE
                ]
            ];
            try{
                $data = $this->DetailListAnimeController->generateDetailAnime(NULL,$listDataAnime);
                echo json_encode($data)."\n\n";
                
                $i++;
            }catch(\Exception $e){
                $dataNotSave[] = array(
                    'Title' => $listAnime['title'],
                    'Index' => $listAnime['name_index'],
                    'id' => $listAnime['id']
                );
                $status = 'Not Complete';
                
            }
            
        }
        $notSave = $TotalHit - $i;

        $response['Total_hit'] = $TotalHit;
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['Total_Data_Save'] = $i.' - Keyword index'.' - '.$startByIndex.':'.$EndByIndex;
        $response['Total_Data_Not_Save'] = $notSave.' - Keyword index'.' - '.$startByIndex.':'.$EndByIndex;
        $response['Data_Not_Save'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}