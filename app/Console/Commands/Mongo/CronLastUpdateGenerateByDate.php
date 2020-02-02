<?php

namespace App\Console\Commands\Mongo;

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


class CronLastUpdateGenerateByDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronLastUpdateGenerateByDateMG:CronLastUpdateGenerateByDateMGV1 {start_date} {end_date} {is_update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronLastUpdateGenerateV1';

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
        $startDate = $this->argument('start_date');
        $EndDate = $this->argument('end_date');
        $isUpdate = filter_var($this->argument('is_update'), FILTER_VALIDATE_BOOLEAN);
        // $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/CronLastUpdateGenerateByDate.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        
        $LastUpdate = [
            'params' => [
                'start_date' => $startDate,
                'end_date' => $EndDate,
                'is_update' => $isUpdate,
                'show_log' => TRUE
            ]
        ];
        
        try{
            $data = $this->LastUpdateEpsAnimController->generateLastUpdateAnime(NULL,$LastUpdate);
            echo json_encode($data)."\n\n";
        }catch(\Exception $e){
            $status = 'Not Complete';
        }
        $response['Hit_date'] =  Carbon::now()->format(DATE_ATOM);
        $response['status'] = $status;
        echo json_encode($response)."\n\n";
    }
}