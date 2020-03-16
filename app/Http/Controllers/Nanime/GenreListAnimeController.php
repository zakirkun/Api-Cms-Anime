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

use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;
use App\Helpers\V1\Converter as Converter;

#Load Models V1
use App\Models\V1\MainModel as MainModel;
use App\Models\V1\Mongo\MainModelMongo as MainModelMongo;


class GenreListAnimeController extends Controller
{ 

    function __construct(){
        $this->mongo = Config::get('mongo');
    }
    /**
     * @author [Prayugo]
     * @create date 2020-01-29 18:22:43
     * @desc [GenreListAnime]
     */
    // ============================= GenreListAnime Save To Mysql ===========================
    public function GenreListAnime(Request $request = NULL, $params = NULL){
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
                $BASE_URL_LIST=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                return $this->GenreListAnimValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Genre Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Genre Anime","Invalid Token", $awal);
        }
    }

    public function GenreListAnimValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
            // Get the latest post in this category and display the titles
            $GenreListAnimeS= $crawler->filter('#list-genre')->each(function ($node,$i) {
                
                $subGenre= $node->filter('.box-body > a')->each(function ($node,$i) {
                    $genre = $node->filter('a')->text('Default text content');
                    
                    $href = $node->filter('a')->attr('href');
                    $subGenre = [
                        "genre"=>$genre,
                        "href"=>$href
                    ];
                    return $subGenre;
                });

                $GenreList = [
                    "subGenre"=>$subGenre
                ];
                return $GenreList;
            });
            
            if($GenreListAnimeS){
                
                for($i=0;$i<count($GenreListAnimeS[0]['subGenre']);$i++){
                    $NameIndex[]=substr($GenreListAnimeS[0]['subGenre'][$i]['genre'],0,1);
                }
                $NameIndex=array_values(array_unique($NameIndex));
                
                // unset($NameIndex[0]);
                
                for($i=0;$i<count($NameIndex);$i++){
                    
                    $ListSubIndex=array();
                    for($j=0;$j<count($GenreListAnimeS[0]['subGenre']);$j++){
                        $NameFilter = substr($GenreListAnimeS[0]['subGenre'][$j]['genre'],0,1);
                        
                        if($NameIndex[$i]===$NameFilter){
                            $KeyListGenreEnc= array(
                                "Genre"=>$GenreListAnimeS[0]['subGenre'][$j]['genre'],
                                "href"=>$BASE_URL."".$GenreListAnimeS[0]['subGenre'][$j]['href']
                            );
                            
                            $KeyListGenre = EnkripsiData::encodeKeyListGenre($KeyListGenreEnc);
                            $Genre = $GenreListAnimeS[0]['subGenre'][$j]['genre'];
                            
                            {#Save Data List Genre
                                $code = Str::slug($Genre);
                                $paramCheck['code'] = md5($code);
                                $checkExist = MainModel::getDataListGenre($paramCheck);
                                
                                if(empty($checkExist)){
                                    $Input = array(
                                        "code" => md5($code),
                                        'slug' => Str::slug($Genre),
                                        "name_index" => $NameIndex[$i],
                                        "genre" => $Genre,
                                        "key_list_genre" => $KeyListGenre,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Save - ".$Genre."-".Carbon::now()->format('Y-m-d H:i:s');
                                    
                                    $save = MainModel::insertGenreListAnimeMysql($Input);
                                }else{
                                    $conditions['id'] = $checkExist[0]['id'];
                                    $Update = array(
                                        "code" => md5($code),
                                        'slug' => Str::slug($Genre),
                                        "name_index" => $NameIndex[$i],
                                        "genre" => $Genre,
                                        "key_list_genre" => $KeyListGenre,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Update - ".$Genre."-".Carbon::now()->format('Y-m-d H:i:s');
                                    $save = MainModel::updateGenreListAnimeMysql($Update,$conditions);
                                }
                            }#End Data List Genre
                            
                        }
                    }
                    
                }
                
                return ResponseConnected::Success("Genre Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("Genre Anime","Page Not Found.", $awal);
            }
        }else{
            return ResponseConnected::PageNotFound("Genre Anime","Page Not Found.", $awal);
        }
    }
    // ============================= End GenreListAnime Save To Mysql===========================

    /**
     * @author [Prayugo]
     * @create date 2020-01-29 18:22:43
     * @desc [generateGenreListAnime]
     */
    // ============================= generateGenreListAnime Save To Mongo ===========================
    public function generateGenreListAnime(Request $request = NULL, $params = NULL){

        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();

        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $code = (isset($param['params']['code']) ? $param['params']['code'] : '');
        $slug = (isset($param['params']['slug']) ? $param['params']['slug'] : '');
        $startNameIndex = (isset($param['params']['start_name_index']) ? $param['params']['start_name_index'] : '');
        $endNameIndex = (isset($param['params']['end_name_index']) ? $param['params']['end_name_index'] : '');
        $title = (isset($param['params']['genre']) ? $param['params']['genre'] : '');

        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        
        #jika pakai range date
        $showLog = (isset($param['params']['show_log']) ? $param['params']['show_log'] : FALSE);
        $parameter = [
            'id' => $id,
            'code' => $code,
            'slug' => $slug,
            'genre' => $title,
            'start_by_index' => $startNameIndex,
            'end_by_index' => $endNameIndex,
            'is_updated' => $isUpdated
        ];
        $genreList = MainModel::getDataListGenre($parameter);
        $errorCount = 0;
        $successCount = 0;
        if(count($genreList)){
            foreach($genreList as $genreList){
                    $conditions = [
                        'id_auto' => $genreList['id'].'-genreListAnime',
                    ];
                    $MappingMongo = array(
                        'id_auto' => $genreList['id'].'-genreListAnime',
                        'source_type' => 'genreList-Anime',
                        'slug' => $genreList['slug'],
                        'code' => $genreList['code'],
                        'genre' => Converter::__normalizeSummary($genreList['genre']),
                        'name_index' => $genreList['name_index'],
                        'keyword' => explode('-',$genreList['slug']),
                        'meta_title' => (Converter::__normalizeSummary(strtolower($genreList['genre']))),
                        'meta_keywords' => explode('-',$genreList['slug']),
                        'meta_tags' => explode('-',$genreList['slug']),
                        'cron_at' => $genreList['cron_at']
                    );
                    
                    $updateMongo = MainModelMongo::updateGenreListAnime($MappingMongo, $this->mongo['collections_genre_list'], $conditions, TRUE);
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
                            $error_id['response']['id'][$key] = $genreList['id']; #set id error generate
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
    }
    // ============================= End generateGenreListAnime Save To Mongo===========================

