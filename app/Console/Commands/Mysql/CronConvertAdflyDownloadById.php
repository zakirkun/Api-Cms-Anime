<?php

namespace App\Console\Commands\Mysql;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\ConvertAdflyDownload;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronConvertAdflyDownloadById extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronConvertAdflyDownloadById:CronConvertAdflyDownloadByIdV1 {start_id} {end_id} {list_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronConvertAdflyDownloadByIdV1';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->ConvertAdflyDownload = new ConvertAdflyDownload();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        ini_set("memory_limit","2048M");
        $startID = $this->argument('start_id');
        $endID = $this->argument('end_id');
        $listIdAdfly = ($this->argument('list_id')) ? explode(',',$this->argument('list_id')) : False ;

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/CronConvertAdflyDownloadByIdV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $j = 0;
        $r = 0;
        $dataNotSave = array();
        #important for get data not save adfly on mysql
        // $param = [
        //     'id_adfly' => TRUE,
        // ];
        // $getDownloadStream = MainModel::getDataNotSaveDownloadStream($param);
        // for($i = 0; $i < count($getDownloadStream) ;$i++){
        //     $dataNotSave[] = array(
        //         'id_stream_anime' => $getDownloadStream[$i]['id_stream_anime'],
        //         'code' => $getDownloadStream[$i]['code'],
        //         'id' => $getDownloadStream[$i]['id']
        //     );
        // }
        // $response['Data_Not_Save'] = $dataNotSave;
        // echo json_encode($response)."\n\n";
        // exit;
        // dd($dataNotSave);
        if($listIdAdfly){
            $TotalHit = count($listIdAdfly);
            for($j = 0 ;$j <count($listIdAdfly) ;$j++ ){
                $param = [
                    'id' => $listIdAdfly[$j],
                ];
                $getDownloadStream = MainModel::getDownloadStream($param);
                for($i = 0; $i < count($getDownloadStream) ;$i++){
                    $dataDownload = [
                        'params' => [
                            'X-API-KEY' => env('X_API_KEY',''),
                            'id' => $getDownloadStream[$i]['id'],
                        ]
                    ];
                    try{
                        $data = $this->ConvertAdflyDownload->AdflyDownload(NULL,$dataDownload);
                        echo json_encode($data)."\n\n";
                        $r++;
                        
                    }catch(\Exception $e){
                        echo "Not Complete id_downlod = ".$getDownloadStream[$i]['id']."\n\n";
                        $dataNotSave[] = array(
                            'id_stream_anime' => $getDownloadStream[$i]['id_stream_anime'],
                            'code' => $getDownloadStream[$i]['code'],
                            'id' => $getDownloadStream[$i]['id']
                        );
                        $status = 'Not Complete';
                    }
                }
            }
        }else{
            $TotalHit = $endID - $startID;
            for($id = $startID ;$id <= $endID ; $id++){
                $param = [
                    'id' => $id,
                ];
                
                $getDownloadStream = MainModel::getDownloadStream($param);
            
                for($i = 0; $i < count($getDownloadStream) ;$i++){
                    $dataDownload = [
                        'params' => [
                            'X-API-KEY' => env('X_API_KEY',''),
                            'id' => $getDownloadStream[$i]['id'],
                        ]
                    ];
                    try{
                        $data = $this->ConvertAdflyDownload->AdflyDownload(NULL,$dataDownload);
                        echo json_encode($data)."\n\n";
                        $r++;
                        
                    }catch(\Exception $e){
                        echo "Not Complete id_downlod = ".$getDownloadStream[$i]['id']."\n\n";
                        $dataNotSave[] = array(
                            'id_stream_anime' => $getDownloadStream[$i]['id_stream_anime'],
                            'code' => $getDownloadStream[$i]['code'],
                            'id' => $getDownloadStream[$i]['id']
                        );
                        $status = 'Not Complete';
                    }
                }
            }
        }
        
        $notSave = $TotalHit - $r;
        
        $response['Total_hit'] = $TotalHit;
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['Total_Data_Save'] = $j;
        $response['Total_Data_Not_Save'] = $notSave;
        $response['Data_Not_Save'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}