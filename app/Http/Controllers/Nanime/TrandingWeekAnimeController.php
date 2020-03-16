<?php
namespace App\Http\Controllers\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \GuzzleHttp\Client;
use \Goutte\Client as GoutteClient; 
use \Tuna\CloudflareMiddleware;
use \GuzzleHttp\Cookie\FileCookieJar;
use \GuzzleHttp\Psr7; 
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Str;

#Helpers
use App\Helpers\V1\MappingResponseMysql as MappingMysql;
use App\Helpers\V1\EnkripsiData as EnkripsiData;
use App\Helpers\V1\ResponseConnected as ResponseConnected;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token
class TrandingWeekAnimeController extends Controller
{
    /**
     * @author [Prayugo]
     * @create date 2020-01-28 18:19:27
     * @desc [TrandingWeekAnime]
     */
    // ============================== TrandingWeekAnime Save To Mysql ========================
    public function TrandingWeekAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        $Users = MainModel::getUser($ApiKey);
        // $Token = $Users[0]['token'];
        if(!empty($Users)){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL_LIST=$BASE_URL;
                return $this->TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Trending Week Anime","Internal Server Error");
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Trending Week Anime","Invalid Token", $awal);
            // return $this->InvalidToken();
        }
    }

    public function TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();
            if($status == 200){
                $TopListDetail= $crawler->filter('.header-image')->each(function ($node,$i) {
                    $subhref = $node->filter('.slider-item')->each(function ($nodel,$i) {
                        $href = $nodel->filter('a')->attr("href");
                        $image=$nodel->filter('.img-responsive')->attr("src");
                        $title = $nodel->filter('.img-responsive')->attr('title');
                        $ListTopnime=array(
                            "href"=>$href,
                            "image"=>$image,
                            "title"=>$title,
                            "status"=>"Ongoing"
                        );
                        
                        return $ListTopnime;
                        });  
                    return $subhref; 
                        
                });
                
                if($TopListDetail){
                    
                    for($i=0;$i<count($TopListDetail[0]);$i++){
                        $href = $BASE_URL."".$TopListDetail[0][$i]['href'];
                        $hrefKeyListAnim = $href;
                        $KeyListAnimEnc=array(
                            "href"=> $href,
                            "Image"=>$TopListDetail[0][$i]['image'],
                            "Title"=>$TopListDetail[0][$i]['title']
                        );
                        $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                        
                        $Title = $TopListDetail[0][$i]['title'];
                        $Title = str_replace("Nonton anime:", "", $Title);
                        $Title = trim($Title);
                        $Image = $TopListDetail[0][$i]['image'];
                        $Status = preg_replace('/(\v|\s)+/', ' ', $TopListDetail[0][$i]['status']);

                        {#save To mysql
                            $slug = Str::slug($Title);
                            $code = $slug;

                            {#save to list Anime
                                $codeListAnime['code'] = md5($code);
                                $listAnime = MainModel::getDataListAnime($codeListAnime);
                                $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                if(empty($listAnime) || $idListAnime == 0){
                                    $KeyListAnimEnc= array(
                                        "Title"=>trim($Title),
                                        "Image"=>"",
                                        "Type"=>"",
                                        "href"=>$hrefKeyListAnim
                                    );
                                    $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                    if(empty($listAnime)){
                                        $Input = array(
                                            'code' => md5($code),
                                            'slug' => $slug,
                                            'title' => $Title,
                                            'key_list_anime' => $KeyListAnim,
                                            'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        $save = MainModel::insertListAnimeMysql($Input);
                                        
                                    }else{
                                        $conditions['id'] = $idListAnime;
                                        $Update = array(
                                            'code' => md5($code),
                                            'slug' => $slug,
                                            'title' => $Title,
                                            'key_list_anime' => $KeyListAnim,
                                            'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                    }
                                    $codeListAnime['code'] = md5($code);
                                    $listAnime = MainModel::getDataListAnime($codeListAnime);
                                    $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                }
                            }#save to list Anime

                            {#save to tranding Week
                                $paramCheck['code'] = md5($code);
                                $checkExist = MainModel::getDataTrendingWeek($paramCheck);
                                if(empty($checkExist)){
                                    $Input = array(
                                        'code' => md5($code),
                                        'slug' => $slug,
                                        "image" => $Image,
                                        "title" => $Title,
                                        "title_alias" => $Title,
                                        "status" => $Status,
                                        'key_list_anime' => $KeyListAnim,
                                        'id_list_anime' => $idListAnime,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Save - ".$Title." ".Carbon::now()->format('Y-m-d H:i:s');
                                    $save = MainModel::insertTrendingWeekMysql($Input);
                                }else{
                                    $conditions['id'] = $checkExist[0]['id'];
                                    $Update = array(
                                        'code' => md5($code),
                                        'slug' => $slug,
                                        "image" => $Image,
                                        "title" => $Title,
                                        "title_alias" => $Title,
                                        "status" => $Status,
                                        'key_list_anime' => $KeyListAnim,
                                        'id_list_anime' => $idListAnime,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] =  "Data Update - ".$Title." ".Carbon::now()->format('Y-m-d H:i:s');
                                    $save = MainModel::updateTrendingWeekMysql($Update,$conditions);
                                }
                            }#save to tranding Week

                        }#End save To mysql
                        
                        

                    }
                    return ResponseConnected::Success("Trending Week Anime", $save, $LogSave, $awal);
                    // return $this->Success($save,$LogSave,$awal);
                }else{
                    return ResponseConnected::PageNotFound("Trending Week Anime","Page Not Found.", $awal);
                    // return $this->PageNotFound();
                }
            }else{
                return ResponseConnected::PageNotFound("Trending Week Anime","Page Not Found.", $awal);
                // return $this->PageNotFound();
            }
    }
    // ============================== End TrandingWeekAnime Save To Mysql ========================

    /**
     * @author [Prayugo]
     * @create date 2020-01-28 18:19:27
     * @desc [generateTrendingWeekAnime]
     */
    // ============================== generateTrendingWeekAnime Save To Mongo ========================
    public function generateTrendingWeekAnime(Request $request = NULL, $params = NULL){

        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();

        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $code = (isset($param['params']['code']) ? $param['params']['code'] : '');
        $slug = (isset($param['params']['slug']) ? $param['params']['slug'] : '');
        $title = (isset($param['params']['title']) ? $param['params']['title'] : '');
        $startDate = (isset($param['params']['start_date']) ? $param['params']['start_date'] : NULL);
        $endDate = (isset($param['params']['end_date']) ? $param['params']['end_date'] : NULL);
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);

        #jika pakai range date
        $showLog = (isset($param['params']['show_log']) ? $param['params']['show_log'] : FALSE);
        $parameter = [
            'id' => $id,
            'code' => $code,
            'slug' => $slug,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_updated' => $isUpdated
        ];

        $trendingWeek = MainModel::getDataTrendingWeek($parameter);

        $errorCount = 0;
        $successCount = 0;
        if(count($trendingWeek)){
            foreach($trendingWeek as $trendingWeek){
                $conditions = [
                    'id_auto' => $trendingWeek['id'].'-trendingWeek',
                ];

                $MappingMongo = array(
                    'id_auto' => $trendingWeek['id'].'-trendingWeek',
                    'id_list_anime' => $trendingWeek['id_list_anime'],
                    'id_detail_anime' => $trendingWeek['id'],
                    'source_type' => 'trendingWeek-Anime',
                    'code' => $trendingWeek['code'],
                    'title' => Converter::__normalizeSummary($trendingWeek['title']),
                    'slug' => $trendingWeek['slug'],
                    'synopsis' => $trendingWeek['synopsis'],
                    'episode' => $dataEps,
                    'image' => $trendingWeek['image'],
                    'status' => $trendingWeek['status'],
                    'rating' => $trendingWeek['rating'],
                    'genre' => explode('|',substr(trim($trendingWeek['genre']),0,-1)),
                    'keyword' => explode('-',$trendingWeek['slug']),
                    'meta_title' => (Converter::__normalizeSummary(strtolower($trendingWeek['title']))),
                    'meta_keywords' => explode('-',$trendingWeek['slug']),
                    'meta_tags' => explode('-',$trendingWeek['slug']),
                    'cron_at' => $trendingWeek['cron_at']
                );

                // $updateMongo = MainModelMongo::updateTrendingWeekAnime($MappingMongo, $this->mongo['collections_detail_anime'], $conditions, TRUE);
                $status = 400;
                $message = '';
                $messageLocal = '';
                if($updateMongo['status'] == 200){
                    $status = 200;
                    $message = 'success';
                    $messageLocal = $updateMongo['message_local'];
                    $successCount++;

                }else{
                    #jika dari cron dan pakai last_date atau pakai generate error
                    #set error id generate
                    if( (!is_null($params) && $endDate == TRUE) || (!is_null($params) && !empty($ids)) ){
                        $error_id['response']['id'][$key] = $ListAnime['id']; #set id error generate
                    }

                    $status = 400;
                    $message = 'error';
                    $messageLocal = serialize($updateMongo['message_local']);
                    $errorCount++;
                }

                #show log response
                if($showLog){
                    $slug = $MappingMongo['slug'];
                    $prefixDate = Carbon::parse($MappingMongo['cron_at'])->format('Y-m-d H:i:s');
                    if($isUpdated == TRUE) $prefixDate = Carbon::parse($MappingMongo['cron_at'])->format('Y-m-d H:i:s');
                    echo $message.' | '.$prefixDate.' | '.$MappingMongo['id_auto'] .' => '.$slug.' | '.$messageLocal."\n";

                }
                
            }
        }else{
            $status = 400;
            $message = 'data tidak ditemukan';
        }

        $response['error'] = $errorCount;
        $response['success'] = $successCount;

        if(!is_null($params)){ # untuk cron
            return $response;
        }else{
            return (new Response($response, 200))
                ->header('Content-Type', 'application/json');
        }
    }
    // ============================== End generateTrendingWeekAnime Save To Mongo ========================
    

}