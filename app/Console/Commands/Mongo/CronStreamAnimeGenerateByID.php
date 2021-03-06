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


class CronStreamAnimeGenerateByID extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CronStreamAnimeGenerateByIDMG:CronStreamAnimeGenerateByIDMGV1 {ID_ListEp_Anime_First} {ID_ListEp_Anime_End} {ID_ListEp_Anime}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron untuk generate data CronStreamAnimeGenerateByIDV1';

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
        $IDListEpsAnimeFirst = ($this->argument('ID_ListEp_Anime_First'));
        $IDListEpsAnimeEnd = ($this->argument('ID_ListEp_Anime_End')) ? $this->argument('ID_ListEp_Anime_End') : $IDListEpsAnimeFirst;
        $IDListEpsAnime = ($this->argument('ID_ListEp_Anime')) ? explode(',',$this->argument('ID_ListEp_Anime')) : False ;
        // $showLog = filter_var($this->argument('show_log'), FILTER_VALIDATE_BOOLEAN);
        $path_log = base_path('storage/logs/generate/mongo');
        $filename = $path_log.'/CronStreamAnimeGenerateByIDV1.json';
        #get file log last date generate
        if(file_exists($filename)) $content = file_get_contents($filename);
        
        $response = [];
        $status = "Complete";
        $i = 0;
        $dataNotSave = array();
        $TotalHit = 0;
        
        if($IDListEpsAnime){
            for($j = 0 ;$j <count($IDListEpsAnime) ;$j++ ){
                $param = [
                    'id' => $IDListEpsAnime[$j],
                ];
                $listEpsAnime = MainModel::getDataListEpisodeAnime($param);
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
                                'id' => $listEpsAnime['id'],
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
                            'id' => $listEpsAnime['id'],
                            'id_detail_anime' => $listEpsAnime['id_detail_anime'],
                            'id_list_anime' => $listEpsAnime['id_list_anime']
                        );
                        $status = 'Not Complete';
                    }
                }
            }
        }else{
            for($j = $IDListEpsAnimeFirst; $j <= $IDListEpsAnimeEnd ; $j++){
                $param = [
                    'id' => $j,
                ];
                $listEpsAnime = MainModel::getDataListEpisodeAnime($param);
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
                        $i++;
                    }catch(\Exception $e){
                        $dataNotSave[] = array(
                            'Episode' => $listEpsAnime['episode'],
                            'id' => $listEpsAnime['id']
                        );
                        $status = 'Not Complete';
                    }
                }
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