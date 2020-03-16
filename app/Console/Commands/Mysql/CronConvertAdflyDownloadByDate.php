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


class CronConvertAdflyDownloadByDate extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronConvertAdflyDownloadByDate:CronConvertAdflyDownloadByDateV1 {start_date} {end_date} {is_update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronConvertAdflyDownloadByDateV1';

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
        $startDate = $this->argument('start_date');
        $EndDate = $this->argument('end_date');
        $isUpdate = filter_var($this->argument('is_update'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/ConvertAdflyDownloadByDateV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        if($isUpdate){
            $startDate = date('Y-m-d');
            $EndDate = '';
        }

        $response = [];
        $param = [
            'code' => '',
            'start_date' => $startDate,
            'end_date' => $EndDate
        ];
        $getDownloadStream = MainModel::getDownloadStream($param);
        
        $status = "Complete";
        $j = 0;
        $dataNotSave = array();
        $TotalHit = (count($getDownloadStream));
        for($i = 0; $i < $TotalHit ;$i++){
            $dataDownload = [
                'params' => [
                    'X-API-KEY' => env('X_API_KEY',''),
                    'id' => $getDownloadStream[$i]['id'],
                ]
            ];
            try{
                $data = $this->ConvertAdflyDownload->AdflyDownload(NULL,$dataDownload);
                echo json_encode($data)."\n\n";
                $j++;
                
            }catch(\Exception $e){
                echo "Not Complete id_downlod = ".$getDownload['id']."\n\n";
                $dataNotSave[] = array(
                    'id_stream_anime' => $getDownload['id_stream_anime'],
                    'code' => $getDownload['code'],
                    'id' => $getDownload['id']
                );
                $status = 'Not Complete';
            }
        }
        $notSave = $TotalHit - $j;
        
        $response['Total_hit'] = $TotalHit;
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['Total_Data_Save'] = $j;
        $response['Total_Data_Not_Save'] = $notSave;
        $response['Data_Not_Save'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}