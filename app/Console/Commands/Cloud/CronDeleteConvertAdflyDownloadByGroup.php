<?php

namespace App\Console\Commands\Cloud;

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


class CronDeleteConvertAdflyDownloadByGroup extends Command
{
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronDeleteConvertAdflyDownloadByGroup:CronDeleteConvertAdflyDownloadByGroupV1 {start_page} {end_page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronDeleteConvertAdflyDownloadByGroupV1';

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
        $startPage = $this->argument('start_page');
        $endPage = $this->argument('end_page');
        // $isUpdate = filter_var($this->argument('is_update'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/CronDeleteConvertAdflyDownloadByGroupV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        // if($isUpdate){
        //     $startDate = date('Y-m-d');
        //     $EndDate = '';
        // }

        $response = [];
        
        $status = "Complete";
        $j = 0;
        $dataNotSave = array();
        $TotalHit = $endPage - $startPage;
        for($id = $startPage ;$id <= $endPage ; $id++){
                $dataDownload = [
                    'params' => [
                        'X-API-KEY' => env('X_API_KEY',''),
                        'start_page' => $id,
                    ]
                ];
                try{
                    $data = $this->ConvertAdflyDownload->DeleteAdflyDownloadbyGroup(NULL,$dataDownload);
                    echo json_encode($data)."\n\n";
                    $j++;
                    
                }catch(\Exception $e){
                    echo "Not Complete pageNumber = ".$id."\n\n";
                    $dataNotSave[] = array(
                        'pageNumber' => $id,
                    );
                    $status = 'Not Complete';
                }
            
        }
        
        $notSave = $TotalHit - $j;
        
        $response['Total_hit'] = $TotalHit;
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['Total_Data_Delete'] = $j;
        $response['Total_Data_Not_Delete'] = $notSave;
        $response['Data_Not_Delete'] = $dataNotSave;
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}