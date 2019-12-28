<?php

namespace App\Console\Commands\Mysql;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\LastUpdateEpsAnimController;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronLastUpdateGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronLastUpdateGenerate:CronLastUpdateGenerateV1  {page_number} {all_list} {show_log} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronLastUpdateGenerate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->LastUpdateEpsAnimController = new LastUpdateEpsAnimController();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $pageNumber = $this->argument('page_number');
        $allList = filter_var($this->argument('all_list'), FILTER_VALIDATE_BOOLEAN);
        $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/DetailListAnimeGenerateByAlfabetV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $TotalHit = 1;
        $notSaveHit = 0;
        $dataNotSave = array();
        if($allList){
            $param = [
                'last_date' => TRUE,
            ];
            $dataLastUpdate = MainModel::getDataLastUpdate($param);
            $TotalHit = $dataLastUpdate[0]['total_search_page'];
            for($i = 1; $i <= $TotalHit; $i++ ){
                $lastUpdate = [
                    'params' => [
                        'X-API-KEY' => env('X_API_KEY',''),
                        'PageNumber' => $i
                    ]
                ];
                try{
                    $data = $this->LastUpdateEpsAnimController->LastUpdateAnime(NULL,$lastUpdate);
                    echo json_encode($data)."\n\n";
                }catch(\Exception $e){
                    echo "Not Save Page Number :".$i."\n\n";
                    $dataNotSave[] = array(
                        'PageNumber' => $i,
                    );
                    $status = 'Not Complete';
                    $notSaveHit++;
                }
                
            }
            $TotDataSave = ($i - $notSaveHit);
        }else{
            $lastUpdate = [
                'params' => [
                    'X-API-KEY' => env('X_API_KEY',''),
                    'PageNumber' => $pageNumber
                ]
            ];
            try{
                $data = $this->LastUpdateEpsAnimController->LastUpdateAnime(NULL,$lastUpdate);
                echo json_encode($data)."\n\n";
            }catch(\Exception $e){
                echo "Not Save Page Number :".$i."\n\n";
                $dataNotSave[] = array(
                    'PageNumber' => $pageNumber,
                );
                $status = 'Not Complete';
                $notSaveHit = 1;
            }
            
            $TotDataSave = 1 - $notSaveHit;
        }

        $response['Total_hit'] = $TotalHit;
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['Total_Data_Save'] = $TotDataSave;
        $response['Total_Data_Not_Save'] = $notSaveHit;
        $response['Data_Not_Save'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}