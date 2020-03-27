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
use \Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client as Client2;
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

#Load Controller
use App\Http\Controllers\Nanime\DetailListAnimeController;

// done but masih proses debuging
class StreamAnimeController extends Controller
{
        public function __construct()
        {
            $this->mongo = Config::get('mongo');
            $this->DetailListAnimeController = new DetailListAnimeController();
        }
    
        /**
         * @author [Prayugo]
         * @create date 2020-01-31 10:13:50
         * @desc [StreamAnime]
         */
        // ========================= StreamAnime Save to Mysql ===================
        public function StreamAnime(Request $request = NULL, $params = NULL){
            $awal = microtime(true);
            if(!empty($request) || $request != NULL){
                $ApiKey = $request->header("X-API-KEY");
                $KeyEpisode = $request->header("KeyEpisode");
                $idDetailAnime_ = $request->header("idDetailAnime");
                $idListAnime_ = $request->header("idListAnime");
                $idListEpisode_ = $request->header("idListEpisode");
            }
            if(!empty($params) || $params != NULL){
                $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
                $KeyEpisode = (isset($params['params']['KeyEpisode']) ? ($params['params']['KeyEpisode']) : '');
                $idDetailAnime_ = (isset($params['params']['idDetailAnime']) ? ($params['params']['idDetailAnime']) : 0);
                $idListAnime_ = (isset($params['params']['idListAnime']) ? ($params['params']['idListAnime']) : 0);
                $idListEpisode_ = (isset($params['params']['idListEpisode']) ? ($params['params']['idListEpisode']) : 0);
            }
            $Users = MainModel::getUser($ApiKey);
            // $Token = $Users[0]['token'];
            $NextEpisode = "";
            $PrevEpisode = "";
            if(!empty($Users)){
                // try{
                    $findCode = strstr($KeyEpisode,'QtYWL');
                    $KeyListDecode = EnkripsiData::DecodeKeyListEps($KeyEpisode);
                    
                    if($findCode){
                        if($KeyListDecode){
                            $subHref = $KeyListDecode->href;
                            $ConfigController = new ConfigController();
                            $BASE_URL = $ConfigController->BASE_URL_ANIME_1;
                            if($NextEpisode){
                                $findCode = strstr($NextEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = EnkripsiData::DecodePaginationEps($NextEpisode);
                                    $URL_Next = $KeyPagiDecode->href;
                                    $BASE_URL_LIST = $URL_Next;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal,$idDetailAnime_,$idListAnime_,$idListEpisode_);
                                }else{
                                    return ResponseConnected::InvalidKeyPagination("Stream Anime","Invalid Pagination", $awal);
                                }
                            }elseif($PrevEpisode){
                                $findCode = strstr($PrevEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = EnkripsiData::DecodePaginationEps($PrevEpisode);
                                    $URL_PREV = $KeyPagiDecode->href;
                                    $BASE_URL_LIST = $URL_PREV;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal,$idDetailAnime_,$idListAnime_,$idListEpisode_);
                                }else{
                                    return ResponseConnected::InvalidKeyPagination("Stream Anime","Invalid Pagination", $awal);
                                }
                            }else{
                                $BASE_URL_LIST = $subHref;
                                return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal,$idDetailAnime_,$idListAnime_,$idListEpisode_);
                            }
                        }else{
                            return ResponseConnected::InvalidKey("Stream Anime","Invalid Key", $awal);
                        }
                        
                    }else{
                        return ResponseConnected::InvalidKey("Stream Anime","Invalid Key", $awal);
                    }

                // }catch(\Exception $e){
                //     return ResponseConnected::InternalServerError("Stream Anime","Internal Server Error",$awal);
                // }
                
            }else{
                return ResponseConnected::InvalidToken("Stream Anime","Invalid Token", $awal);
            }
        }

        public function FilterHreftDetail($DetailList){
            $subHrefDetail = explode("<a",$DetailList);
            $substring = substr($subHrefDetail[1], 0, strpos($subHrefDetail[1], '>'));
            $hrefDetField = substr($substring, strpos($substring, "f") + 1);
            $hrefDetField = str_replace('=','',$hrefDetField);
            return $hrefDetField;
        }

        public function StreamValue($BASE_URL_LIST,$BASE_URL,$awal,$idDetailAnime_,$idListAnime_,$idListEpisode_){
            $client = new Client([
                'timeout' => 10.0,
                'cookie' => true,
                'cookies' => new FileCookieJar('cookies.txt')
                ]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            // Connect a 2nd user using an isolated browser and say hi!
            // untuk get data setelah .com url
            $urlSlug = substr(strrchr($BASE_URL_LIST, '.'), 3); # xample /movie/naruto-shipudden
            $BASE_URL = substr($BASE_URL, 0, -1); #xample https://nanime.yt
            $BASE_URL_LIST = $BASE_URL.$urlSlug;
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();
            
            if($status == 200){
                // for get iframe from javascript
                $SubListDetail = $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $synopsis = $node->filter('.attachment-text')->text('Default text content');
                    $imageUrl = $node->filter('.attachment-img')->attr('src');
                    
                    $DetailList = $node->filter('tbody')->each(function ($node,$i) {
                        $DetailListC1 = $node->filter('tr')->each(function ($node,$i) {
                            $DetailListC2 = $node->filter('td')->each(function ($node,$i) {
                                $ListDetail = $node->filter('td')->html();
                                return $ListDetail;
                            });
                            return $DetailListC2;
                        });
                        return $DetailListC1;
                    });

                    $JudulAlternatif = $DetailList[0][1][1];
                    $Rating = $DetailList[0][2][1]; 
                    $Votes = $DetailList[0][3][1];
                    $TotalEpisode = $DetailList[0][5][1];
                    $HariTayang = $DetailList[0][7][1];
                    $hrefDetail = self::FilterHreftDetail($DetailList[0][0][1]);

                    {#query get status
                        $SubStatus = explode("<a",$DetailList[0][4][1]);
                        $status = '';
                        for($j=1;$j<count($SubStatus);$j++){
                            $statusField = substr($SubStatus[$j], strpos($SubStatus[$j], ">") + 1);
                            $status = trim(str_replace('</a>','',$statusField));
                        }
                    }#End query get status

                    $Subjudul = explode("<a",$DetailList[0][0][1]);
                    $judul = '';
                    for($j=1;$j<count($Subjudul);$j++){
                        $judulField = substr($Subjudul[$j], strpos($Subjudul[$j], ">") + 1);
                        $judul .= trim(str_replace('</a>','',$judulField)).' ';
                    }
                    
                    $SubgenreFilter = $DetailList[0][8][1];
                    $detGenre = explode("<a", $SubgenreFilter);
                    $genre = array();
                    for($j=1;$j<count($detGenre);$j++){
                        $genreField = substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
                        $genre[] = trim(str_replace('</a>','',$genreField));
                    } 
                
                    $SubDetail = array(
                        "Title" => $judul,
                        "JudulAlternatif" => $JudulAlternatif,
                        "Rating" => $Rating,
                        "Votes" => $Votes,
                        "Status" => $status,
                        "TotalEpisode"=> $TotalEpisode,
                        "HariTayang" => $HariTayang,
                    );    
                    $slugDetail = substr(strrchr($hrefDetail, '/'), 1);
                    $SubListDetail = array(
                        "slugDetail" => $slugDetail,
                        "subDetail" => $SubDetail,
                        "synopsis" => $synopsis,
                        "image" => $imageUrl,
                        "genre" => $genre,
                        
                    );
                    return $SubListDetail; 
                });
                
                $SubMirror = $crawler->filter('#change-server')->each(function ($node,$i) {
                        $SubServer = $node->filter('option')->each(function ($node,$i) {
                            $NameServer = $node->filter('option')->text('Default text content');
                            $IframeSrc = $node->filter('option')->attr('value');   
                            $ListMirror = [
                                'NameServer' => $NameServer,
                                'slugServer' => '',
                                'IframeSrc'  => $IframeSrc
                            ];
                            
                            return $ListMirror;
                        });                        
                        return $SubServer;
                });

                $SubDownload = $crawler->filter('.episode_list')->each(function ($node,$i) {
                    $SubServer = $node->filter('a')->each(function ($node,$i) {
                        $NameDowloadServer = $node->filter('a')->text('Default text content');
                        $hrefDownload = $node->filter('a')->attr('href');   
                        $ListMirror = [
                            'NameDowloadServer' => $NameDowloadServer,
                            'slugServer' => '',
                            'hrefDownload'  => $hrefDownload
                        ];
                        
                        return $ListMirror;
                    });                        
                    return $SubServer;
                });
                
                if($SubListDetail){
                    {#List Property
                        $hrefEpisode = $BASE_URL_LIST ;
                        $slugEps = substr(strrchr($hrefEpisode, '/'), 1);
                        $Title = str_replace('-',' ',trim($slugEps));
                    }#End List Property

                    if(empty($idListEpisode_)){
                        $codeListEps['code'] = md5(Str::slug($slugEps));
                        $getDataEpisode = MainModel::getDataListEpisodeAnime($codeListEps);
                        $idDetailAnime_ = (!empty($getDataEpisode)) ? $getDataEpisode[0]['id_detail_anime'] : 0;
                        $idListAnime_ = (!empty($getDataEpisode)) ? $getDataEpisode[0]['id_list_anime'] : 0;
                        $idListEpisode_ = (!empty($getDataEpisode)) ? $getDataEpisode[0]['id'] : 0;
                    }
                    
                    {#cek query Relasi
                        {#save to StreamAnime
                            $SlugEps = Str::slug($slugEps);
                            $codeStream['code'] = md5($SlugEps);
                            $getStreamAnime = MainModel::getStreamAnime($codeStream);
                            if(empty($getStreamAnime)){
                                $Input = array(
                                    'code' => md5($SlugEps),
                                    'slug' => $SlugEps,
                                    'title' => $Title,
                                    'id_list_anime' => $idListAnime_,
                                    'id_list_episode' => $idListEpisode_,
                                    'id_detail_anime' => $idDetailAnime_,
                                    'next_episode' => '',
                                    'key_list_anime' => '',
                                    'prev_episode' => '',
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                
                                $save = MainModel::insertStreamAnimeMysql($Input);
                                $getStreamAnime = MainModel::getStreamAnime($codeStream);
                                $serverSave = self::saveServerDownload($SubDownload,$getStreamAnime);
                                $LogSave = $this->saveServerStream($SubMirror,$getStreamAnime);
            
                            }else{
                                $conditions['id'] = $getStreamAnime[0]['id'];
                                $Update = array(
                                    'code' => md5($SlugEps),
                                    'slug' => $SlugEps,
                                    'title' => $Title,
                                    'id_list_anime' => $idListAnime_,
                                    'id_list_episode' => $idListEpisode_,
                                    'id_detail_anime' => $idDetailAnime_,
                                    'next_episode' => '',
                                    'key_list_anime' => '',
                                    'prev_episode' => '',
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                
                                $save = MainModel::updateStreamAnimeMysql($Update,$conditions);
                                $serverSave = self::saveServerDownload($SubDownload,$getStreamAnime);
                                $LogSave = self::saveServerStream($SubMirror,$getStreamAnime);
                            }
                        }#Endsave to StreamAnime
                    }#End cek query Relasi

                    return ResponseConnected::Success("Stream Anime", $save, $LogSave, $awal);
                }else{
                    return ResponseConnected::PageNotFound("Stream Anime","Page Not Found.", $awal);
                }
            }else{
                return ResponseConnected::PageNotFound("Stream Anime","Page Not Found.", $awal);
            }
        }

        public static function saveServerStream($SubMirror,$getStreamAnime){
            $ListServer = array();
            $idStreamAnime = $getStreamAnime[0]['id'];
            $Title = $getStreamAnime[0]['title'];
            $SlugEps = $getStreamAnime[0]['slug'];
            $LogSave = array();
            for($i = 0;$i < count($SubMirror[0]);$i++){
                $NameServer = trim($SubMirror[0][$i]['NameServer']);
                $IframeSrc = ($SubMirror[0][$i]['IframeSrc']);
                $SlugServer = $NameServer.'-'.$idStreamAnime;
                $SlugServer = Str::slug($SlugServer);
                $paramCheck['code'] = md5($SlugServer);
                $paramCheck['id_stream_anime'] = $idStreamAnime;
                $checkExist1 = MainModel::getServerStream($paramCheck);
                 
                if(empty($checkExist1)){
                    $Input = array(
                        'code' => md5($SlugServer),
                        'name_server' => $NameServer,
                        'iframe_src' => $IframeSrc,
                        "id_stream_anime" => $idStreamAnime,
                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                    );
                    $LogSave [] = "Data Save - ".$NameServer."-".$Title;
                    $save = MainModel::insertServerStreamMysql($Input);
                }else{
                    $conditions['id'] = $checkExist1[0]['id'];
                    $Update = array(
                        'code' => md5($SlugServer),
                        'name_server' => $NameServer,
                        'iframe_src' => $IframeSrc,
                        "id_stream_anime" => $idStreamAnime,
                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                    );
                    $LogSave [] = "Data Update - ".$NameServer."-".$Title;
                    $save = MainModel::updateServerStreamMysql($Update,$conditions);
                }
            }
            return $LogSave;
        }

        public static function saveServerDownload($SubDownload,$getStreamAnime){
            $ListServer = array();
            $idStreamAnime = $getStreamAnime[0]['id'];
            $Title = $getStreamAnime[0]['title'];
            $SlugEps = $getStreamAnime[0]['slug'];
            $LogSave = array();
            for($i = 0;$i < count($SubDownload[0]);$i++){
                $NameDowloadServer = trim($SubDownload[0][$i]['NameDowloadServer']);
                $hrefDownload = ($SubDownload[0][$i]['hrefDownload']);
                $SlugDownloadServer = $NameDowloadServer.'-'.$idStreamAnime;
                $SlugDownloadServer = Str::slug($SlugDownloadServer);
                $paramCheck['code'] = md5($SlugDownloadServer);
                $paramCheck['id_stream_anime'] = $idStreamAnime;
                $checkExist1 = MainModel::getDownloadStream($paramCheck);
                
                if(empty($checkExist1)){
                    $Input = array(
                        'code' => md5($SlugDownloadServer),
                        'name_server' => $NameDowloadServer,
                        'link_download' => $hrefDownload,
                        "id_stream_anime" => $idStreamAnime,
                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                    );
                    $LogSave [] = "Data Download Save - ".$NameDowloadServer."-".$Title;
                    $save = MainModel::insertDownloadStreamMysql($Input);
                }else{
                    $conditions['id'] = $checkExist1[0]['id'];
                    $Update = array(
                        'code' => md5($SlugDownloadServer),
                        'name_server' => $NameDowloadServer,
                        'link_download' => $hrefDownload,
                        "id_stream_anime" => $idStreamAnime,
                    );
                    $LogSave [] = "Data Download Update - ".$NameDowloadServer."-".$Title;
                    $save = MainModel::updateDownloadStreamMysql($Update,$conditions);
                }
            }
            return $LogSave;
        }

        // ========================= End StreamAnime Save to Mysql ===================

        /**
         * @author [Prayugo]
         * @create date 2020-02-01 23:46:34
         * @modify date 2020-02-01 23:46:34
         * @desc [generateStreamAnime]
         */
        // ========================= generateStreamAnime Save to Mongo ===================
        public function generateStreamAnime(Request $request = NULL, $params = NULL){

            $param = $params; # get param dari populartopiclist atau dari cron
            if(is_null($params)) $param = $request->all();

            $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
            $idListAnime = (isset($param['params']['id_list_anime']) ? $param['params']['id_list_anime'] : NULL);
            $idListEpisode = (isset($param['params']['id_list_episode']) ? $param['params']['id_list_episode'] : NULL);
            $idDetailAnime = (isset($param['params']['id_detail_anime']) ? $param['params']['id_detail_anime'] : NULL);
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
                'id_list_anime' => $idListAnime,
                'id_list_episode' => $idListEpisode,
                'id_detail_anime' => $idDetailAnime,
                'code' => $code,
                'slug' => $slug,
                'title' => $title,
                'start_date' => $startDate, 
                'end_date' => $endDate,
                'is_updated' => $isUpdated
            ];
            $getStreamData = MainModel::getStreamAnimeJoin($parameter);
            
            $errorCount = 0;
            $successCount = 0;
            if(count($getStreamData)){
                foreach($getStreamData as $getStreamData){
                    $conditions = [
                        'id_auto' => $getStreamData['id'].'-streamAnime',
                    ];
                    $param = [
                        'id_stream_anime' => $getStreamData['id']
                    ];
                    $getServerStream = MainModel::getServerStream($param);
                    $getDownloadStream = MainModel::getDownloadStream($param);
                    
                    $dataServer = array();
                    $adflyDownload = array();
                    foreach($getServerStream as $getServerStream){
                        $dataServer[] = array(
                            'id_server' => $getServerStream['id'],
                            'name_server' => $getServerStream['name_server'],
                            'iframe_src' => $getServerStream['iframe_src']
                        );
                    }
                    foreach($getDownloadStream as $getDownloadStreamV){
                        $adflyDownload[] = array(
                            'id_download' => $getDownloadStreamV['id'],
                            'name_download' => $getDownloadStreamV['name_server'],
                            'adfly_link' => $getDownloadStreamV['adfly_link_download']
                        );
                    }
                    $updateMongo['status'] = 404;
                    $MappingMongo = array();
                    if(count($dataServer) > 0){
                        $MappingMongo = array(
                            'id_auto' => $getStreamData['id'].'-streamAnime',
                            'id_stream_anime' => $getStreamData['id'],
                            'id_list_episode' => $getStreamData['id_list_episode'],
                            'id_detail_anime' => $getStreamData['id_detail_anime'],
                            'id_list_anime' => $getStreamData['id_list_anime'],
                            'source_type' => 'stream-Anime',
                            'code' => $getStreamData['code'],
                            'slug' => $getStreamData['slug'],
                            'title' => Converter::__normalizeSummary($getStreamData['title']),
                            'synopsis' => $getStreamData['synopsis'],
                            'data_server' => $dataServer,
                            'data_download' => [
                                'adfly' => $adflyDownload
                            ],
                            'image' => $getStreamData['image'],
                            'status' => $getStreamData['status'],
                            'rating' => $getStreamData['rating'],
                            'episode_total' => $getStreamData['episode_total'],
                            'genre' => explode('|',substr(trim($getStreamData['genre']),0,-1)),
                            'keyword' => explode('-',$getStreamData['slug']),
                            'meta_title' => (Converter::__normalizeSummary(strtolower($getStreamData['title']))),
                            'meta_keywords' => explode('-',$getStreamData['slug']),
                            'meta_tags' => explode('-',$getStreamData['slug']),
                            'cron_at' => $getStreamData['cron_at']
                        );
                        $updateMongo = MainModelMongo::updateStreamAnime($MappingMongo, $this->mongo['collections_detail_anime'], $conditions, TRUE);
                    }
                    
                    
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
                            $error_id['response']['id'][$key] = $getStreamData['id']; #set id error generate
                        }
    
                        $status = 400;
                        $message = 'error';
                        $messageLocal = 'Data Not Found';
                        $errorCount++;
                    }
    
                    #show log response
                    if($showLog){
                        $slug = (count($MappingMongo) > 0) ? $MappingMongo['slug'] : 'Not Found';
                        $prefixDate = (count($MappingMongo) > 0) ? Carbon::parse($MappingMongo['cron_at'])->format('Y-m-d H:i:s') : '';
                        if($isUpdated == TRUE) $prefixDate = (count($MappingMongo) > 0) ? Carbon::parse($MappingMongo['cron_at'])->format('Y-m-d H:i:s') : '';
                        $id_auto = (count($MappingMongo) > 0) ? $MappingMongo['id_auto'] : '' ;
                        echo $message.' | '.$prefixDate.' | '. $id_auto.' => '.$slug.' | '.$messageLocal."\n";
    
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
        // ========================= End generateStreamAnime Save to Mongo ===================
}