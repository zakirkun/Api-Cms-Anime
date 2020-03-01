<?php

namespace App\Console\Commands\Mysql;

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


class CronStreamAnimeGenerateByAscID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronStreamAnimeGenerateByAscID:CronStreamAnimeGenerateByAscIDV1 {start_id_episode} {end_id_episode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronStreamAnimeGenerateByAscIDV1';

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
        $start_id_episode = $this->argument('start_id_episode');
        $end_id_episode = $this->argument('end_id_episode');
        // $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);

        $path_log = base_path('storage/logs/generate/mysql');
        $filename = $path_log.'/CronStreamAnimeGenerateByAscIDV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $j = 0;
        $dataNotSave = array();
        
        for($i = $start_id_episode; $i <= $end_id_episode ; $i++ ){
            $param = [
                'id' => $i,
            ];
            
            $listEpsAnime = MainModel::getDataListEpisodeAnime($param);
            if(count($listEpsAnime) > 0){
                foreach($listEpsAnime as $listEpsAnime){
                    $StreamAnime = [
                        'params' => [
                            'X-API-KEY' => env('X_API_KEY',''),
                            'KeyEpisode' => $listEpsAnime['key_episode'],
                            'idDetailAnime' => $listEpsAnime['id_detail_anime'],
                            'idListAnime' => $listEpsAnime['id_list_anime'],
                            'idListEpisode' => $listEpsAnime['id']
                        ]
                    ];
                    try{
                        $data = $this->StreamAnimeController->StreamAnime(NULL,$StreamAnime);
                        echo json_encode($data)."\n\n";
                        $j++;
                    }catch(\Exception $e){
                        echo "Not Complete episode = ".$listEpsAnime['episode'].' id ='.$listEpsAnime['id']."\n\n";
                        $dataNotSave[] = array(
                            'Episode' => $listEpsAnime['episode'],
                            'KeyEpisode' => $listEpsAnime['key_episode'],
                            'id' => $listEpsAnime['id']
                        );
                        $status = 'Not Complete';
                    }
                }
            }else{
                echo "Data Episode dengan ID =".$i." Tidak Ada\n\n";
            }
        }
        $TotalHit = count(range($start_id_episode,$end_id_episode))-1;
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