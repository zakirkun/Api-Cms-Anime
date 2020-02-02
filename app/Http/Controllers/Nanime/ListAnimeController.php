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
use \App\User;
use Illuminate\Support\Str;
use Config;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Models V1
use App\Models\V1\MainModel as MainModel;
use App\Models\V1\Mongo\MainModelMongo as MainModelMongo;

// done
class ListAnimeController extends Controller
{
    function __construct(){
        $this->mongoC = Config::get('mongo');
    }
    /**
     * @author [Prayugo]
     * @email [example@mail.com]
     * @desc [ListAnime & ListAnimeValue Save Mysql]
     */
    // ======================= List Anime save to Mysql======================
    public function ListAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        $generateKey = bin2hex(random_bytes(16));
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL_LIST=$ConfigController->BASE_URL_LIST_ANIME_1;
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                return $this->ListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("List Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("List Anime","Invalid Token", $awal);
        }
    }
    public function ListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
            // Get the latest post in this category and display the titles
            $nodeValues = $crawler->filter('.col-md-7')->each(function ($node,$i) {
                $List= $node->filter('.table-responsive')->each(function ($nodel,$i) {
                    $NameIndex =$nodel->filter('.col-md-12')->text('Default text content');
                    
                    $SubList= $nodel->filter('.col-md-6')->each(function ($nodel,$i) {
                        $Title = $nodel->filter('a')->text('Default text content');
                        $href = $nodel->filter('a')->attr('href');
                        $deleteEmail = ['[','email','protected',']',','];
                        // if (stripos((Converter::__normalizeSummary($Title)),'[email') !== false
                        // || stripos($Title,'&') || stripos($Title,';')){
                        //     $Title = substr($href, strrpos($href, '/' )+1);
                        //     $Title = str_replace("-"," ",$Title);
                        // }else{
                        //     $Title = $Title;
                        // }
                        $item = [
                            'TitleAlias' => $Title,
                            'Title'=>$Title,
                            'href'=>$href,
                            'type'=>""
                        ];
                        return $item;
                    });
                    
                    $items = [
                        'List'=>$SubList,
                        'NameIndex'=>$NameIndex
                    ];
                    
                    return $items;
                });
                return $List;
            });
            
            if($nodeValues){
                $ListAnime = array(); 
                $NameIndex= array();
                foreach($nodeValues[0] as $item){
                    $NameIndexVal = trim($item['NameIndex']);
                    $List = $item['List'];
                    $ListSubIndex = array();
                    foreach($List as $List){
                        $filter = substr(preg_replace('/(\v|\s)+/', ' ', $List['Title']), 0, 2);
                        $Title = $List['Title'];
                        $TitleAlias = $List['TitleAlias'];
                        $href = $List['href'];
                        $Title = Converter::__normalizeTitle($Title,$href);
                        $TitleAlias = Converter::__normalizeTitle($TitleAlias,$href);
                        
                        $Type = $List['type'];
                        if($NameIndexVal=='##'){
                            if(!ctype_alpha($filter) || ctype_alpha($filter)){
                                $KeyListAnimEnc = array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                
                                $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                
                                $ListSubIndex[] = array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            }
                        }else{
                                $KeyListAnimEnc = array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                
                                $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                
                                $ListSubIndex[] = array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            
                        }
                        $Slug = Str::slug($Title);
                        $code = $Slug;
                        
                        {#Save To List Anime
                            $paramCheck['code'] = md5($code);
                            $checkExist = MainModel::getDataListAnime($paramCheck);
                            if(empty($checkExist)){
                                $Input = array(
                                    'code' => md5($code),
                                    'slug' => $Slug,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => $NameIndexVal,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $LogSave [] = "Data Save - ".$Title;
                                $save = MainModel::insertListAnimeMysql($Input);
                            }else{
                                $conditions['id'] = $checkExist[0]['id'];
                                $Update = array(
                                    'code' => md5($code),
                                    'slug' => $Slug,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => $NameIndexVal,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $LogSave [] =  "Data Update - ".$Title;
                                $save = MainModel::updateListAnimeMysql($Update,$conditions);
                            }
                        }#End Save To List Anime
                    }
                    
                }
                
                return ResponseConnected::Success("List Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
            }  
        }else{
            return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
        }
    }
    // ======================= End List Anime save to Mysql======================
    
    /**
     * @author [Prayugo]
     * @email [example@mail.com]
     * @desc [ListAnimeGenerate & ListAnimeValue Save Mongo]
     */
    // ======================= List Anime Generate save to Mongo ======================
    public function ListAnimeGenerate(Request $request = NULL, $params = NULL){

        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();

        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $code = (isset($param['params']['code']) ? $param['params']['code'] : '');
        $slug = (isset($param['params']['slug']) ? $param['params']['slug'] : '');
        $title = (isset($param['params']['title']) ? $param['params']['title'] : '');
        $startNameIndex = (isset($param['params']['start_name_index']) ? $param['params']['start_name_index'] : '');
        $endNameIndex = (isset($param['params']['end_name_index']) ? $param['params']['end_name_index'] : '');
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
            'start_by_index' => $startNameIndex,
            'end_by_index' => $endNameIndex,
            'is_updated' => $isUpdated
        ];
        
        $ListAnime = MainModel::getDataListAnimeJoin($parameter);
        $errorCount = 0;
        $successCount = 0;
        if(count($ListAnime)){
            foreach($ListAnime as $ListAnime){
                $conditions = [
                    'id_auto' => $ListAnime['id'].'-listAnime',
                ];
                $MappingMongo = array(
                    'id_auto' => $ListAnime['id'].'-listAnime',
                    'id_list_anime' => $ListAnime['id'],
                    'id_detail_anime' => $ListAnime['id_detail_anime'],
                    'source_type' => 'list-Anime',
                    'code' => $ListAnime['code'],
                    'title' => Converter::__normalizeSummary($ListAnime['title']),
                    'slug' => $ListAnime['slug'],
                    'name_index' => $ListAnime['name_index'],
                    'image' => $ListAnime['image'],
                    'status' => $ListAnime['status'],
                    'rating' => $ListAnime['rating'],
                    'genre' => explode('|',substr(trim($ListAnime['genre']),0,-1)),
                    'keyword' => explode('-',$ListAnime['slug']),
                    'meta_title' => (Converter::__normalizeSummary(strtolower($ListAnime['title']))),
                    'meta_keywords' => explode('-',$ListAnime['slug']),
                    'meta_tags' => explode('-',$ListAnime['slug']),
                    'cron_at' => $ListAnime['cron_at']
                );
                
                $updateMongo = MainModelMongo::updateListAnime($MappingMongo, $this->mongoC['collections_list_anime'], $conditions, TRUE);
                
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

    // ======================= End List Anime Generate save to Mongo======================
}
