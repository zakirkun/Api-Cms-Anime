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


class CronDetailListAnimeGenerateByDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronDetailListAnimeGenerateByDateMG:DetailListAnimeGenerateByDateMGV1  {start_date} {end_date} {is_update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data DetailListAnimeGenerateByDateV1';

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
        $startDate = $this->argument('start_date');
        $EndDate = $this->argument('end_date');
        $isUpdate = filter_var($this->argument('is_update'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/DetailListAnimeGenerateByDateV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        if($isUpdate){
            $startDate = date('Y-m-d');
            $EndDate = '';
        }
        
        $response = [];
        $param = [
            'start_date' => $startDate,
            'end_date' => $EndDate
        ];
        
        $listAnime = MainModel::getDataDetailAnime($param);
        
        $status = "Complete";
        $i = 0;
        $dataNotSave = array();
        $TotalHit = (count($listAnime));
        foreach($listAnime as $listAnime){
            $listDataAnime = [
                'params' => [
                    'id' => $listAnime['id'],
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
        $response['Total_Data_Save'] = $i;
        $response['Total_Data_Not_Save'] = $notSave;
        $response['Data_Not_Save'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}