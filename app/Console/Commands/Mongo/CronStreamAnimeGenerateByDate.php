<?php

namespace App\Console\Commands\Mongo;

use Illuminate\Console\Command;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

#Load Controller
use App\Http\Controllers\Nanime\StreamAnimeController;

/*Load Component*/
use Cache;
use Config;
use Carbon\Carbon;

#Load Models V1
use App\Models\V1\MainModel as MainModel;


class CronStreamAnimeGenerateByDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronStreamAnimeGenerateByDateMG:CronStreamAnimeGenerateByDateMGV1 {start_date} {end_date} {is_update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronStreamAnimeGenerateByDateMGV1';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->StreamAnimeController = new StreamAnimeController();
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

        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/CronStreamAnimeGenerateByDateMGV1.json';
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
        $listEpsAnime = MainModel::getDataListEpisodeAnime($param);
        
        $status = "Complete";
        $i = 0;
        $dataNotSave = array();
        $TotalHit = (count($listEpsAnime));
        foreach($listEpsAnime as $listEpsAnime){
            $StreamAnime = [
                'params' => [
                    'id_list_episode' => $listEpsAnime['id'],
                    'show_log' => TRUE
                ]
            ]; 
            try{
                $data = $this->StreamAnimeController->generateStreamAnime(NULL,$StreamAnime);
                echo json_encode($data)."\n\n";
                if($data['success'] == 0){
                    $dataNotSave[] = array(
                        'Episode' => $listEpsAnime['episode'],
                        'id_list_ep' => $listEpsAnime['id'],
                        'id_detail_anime' => $listEpsAnime['id_detail_anime'],
                        'id_list_anime' => $listEpsAnime['id_list_anime']
                    );
                    $status = 'Not Complete';
                }else{
                    $i++;
                }
            }catch(\Exception $e){
                $dataNotSave[] = array(
                    'Episode' => $listEpsAnime['episode'],
                    'id_list_ep' => $listEpsAnime['id'],
                    'id_detail_anime' => $listEpsAnime['id_detail_anime'],
                    'id_list_anime' => $listEpsAnime['id_list_anime']
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