<?php
namespace App\Http\Controllers\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \Symfony\Component\DomCrawler\Crawler;
use \GuzzleHttp\Client;
use \Goutte\Client as GoutteClient; 
use \Tuna\CloudflareMiddleware;
use \GuzzleHttp\Cookie\FileCookieJar;
use \GuzzleHttp\Psr7;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use \Jenssegers\Agent\Agent;
use Illuminate\Support\Str;

use Config;

#Helpers
use App\Helpers\V1\MappingResponseMysql as MappingMysql;
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Controller
use App\Http\Controllers\Nanime\DetailListAnimeController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;
use App\Models\V1\Mongo\MainModelMongo as MainModelMongo;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [__construct]
     */
    // ================  __construct =========================================
    public function __construct()
    {
        $this->DetailListAnimeController = new DetailListAnimeController();
        $this->mongo = Config::get('mongo');
    }
    // ================ End __construct =========================================

    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [LastUpdateAnime]
     */
    // ================  LastUpdateAnime Save To Mysql =========================================
    public function LastUpdateAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);        
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
            $PageNumber = $request->header("PageNumber") ? $request->header("PageNumber") : 1;
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
            $PageNumber = (isset($params['params']['PageNumber'])? ($params['params']['PageNumber']) : 1);
        }
        $Users = MainModel::getUser($ApiKey);
        // $Token = $Users[0]['token'];
        if(!empty($Users)){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL = $ConfigController->BASE_URL_ANIME_1;
                if($PageNumber < 2){
                    $BASE_URL_LIST = $BASE_URL;
                }else{
                    $BASE_URL_LIST = $BASE_URL."page/".$PageNumber;
                }
                return $this->LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Last Update Anime","Internal Server Error",$awal);
            // }
            
        }else{
            
            return ResponseConnected::InvalidToken("Last Update Anime","Invalid Token", $awal);
        }
    }

    public function FilterHreftEpisode($value){
        $subHref = explode("<a", $value);
        $valueHref =str_replace("href","",$subHref[1]);
        $filterValue = substr($valueHref, strpos($valueHref, '"') + 1);
        $href = strtok($filterValue, '"');
        return $href;
    }

    public function FilterPageEpisode($value){
        $subHref = explode("<a", $value);
        $countHref=count($subHref);
        if($countHref>=8){
            $i=$countHref-1;
        }else{
            $i=$countHref;
        }
        $valueHref = str_replace("href","",$subHref[$i]);
        $filterValue = substr($valueHref, strpos($valueHref, '?') + 1);
        $filterValue01 = substr($filterValue, strpos($filterValue, '=') + 1);
        $href = strtok($filterValue01, '"');
        return $href;
    }

    public function FilterHreftDetail($DetailList){
        $subHrefDetail = explode("<a",$DetailList);
        $substring = substr($subHrefDetail[1], 0, strpos($subHrefDetail[1], '>'));
        $hrefDetField = substr($substring, strpos($substring, "f") + 1);
        $hrefDetField = str_replace('=','',$hrefDetField);
        return $hrefDetField;
    }

    public function LastUpdateAnimValue($PageNumber, $BASE_URL_LIST,$BASE_URL, $awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        // $Body=(string)$response->getBody();
        if($status == 200){
            
            $LastUpdateEps= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                
                $subhref = $node->filter('.col-sm-3')->each(function ($nodel, $i) {
                    $href = $nodel->filter('a')->attr("href");
                    $image = $nodel->filter('img')->attr("src");
                    $titleAlias = $nodel->filter('.post-title')->text('Default text content');
                    $title = $nodel->filter('.post-title')->attr("title");
                    $status =  $nodel->filter('.status')->text('Default text content');
                    $episode =  $nodel->filter('.episode')->text('Default text content');
                    $ListUpdtnime = array(
                            "hrefSingleList" => $href,
                            "image" => $image,
                            "titleAlias" => $titleAlias,
                            "title" => $title,
                            "status" => $status,
                            "episode" => $episode,
                            "slugDetail" => substr(strrchr($href, '/'), 1),
                    );
                    
                    return $ListUpdtnime;
                });
                return $subhref; 
            });
            
            if($LastUpdateEps){
                
                $SingleEpisode = array();
                $hrefKeyListAnim = array();
                for($i=0;$i<count($LastUpdateEps[0]);$i++){
                    $SingleListHref = $LastUpdateEps[0][$i]['hrefSingleList'];
                    $hrefKeyListAnim[] = $SingleListHref;
                    // $crawler2 = $goutteClient->request('GET', 'https://nanime.yt/anime/digimon-adventure-tri');
                    $crawler2 = $goutteClient->request('GET', $SingleListHref);
                    $response2 = $goutteClient->getResponse();
                    try{
                        $DetailHref =  $crawler2->filter('#table-episode')->html();
                    }catch(\Exception $e){
                        $DetailHref ="";
                    }
                    
                    if($DetailHref){
                        #Episode Detail
                        $SubListDetail = $crawler2->filter('.col-md-7')->each(function ($node,$i) {
                            $synopsis = $node->filter('.attachment-text')->text('Default text content');
                            
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

                            $SubStatus = explode("<a",$DetailList[0][4][1]);
                            $status = '';
                            for($j=1;$j<count($SubStatus);$j++){
                                $statusField = substr($SubStatus[$j], strpos($SubStatus[$j], ">") + 1);
                                $status .= trim(str_replace('</a>','',$statusField));
                            }
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
                                "Judul" => $judul,
                                "JudulAlternatif" => $JudulAlternatif,
                                "Rating" => $Rating,
                                "Votes" => $Votes,
                                "Status" => $status,
                                "TotalEpisode"=> $TotalEpisode,
                                "HariTayang" => $HariTayang,

                            );
                            
                            if(count($DetailList[1]) > 0){
                                $href = self::FilterHreftEpisode($DetailList[1][0][0]);
                            } else{
                                $href = 'Null';
                            }
                            
                            $SubListDetail = array(
                                "subDetail" => $SubDetail,
                                "synopsis" => $synopsis,
                                "genre" => $genre,
                                "hrefEpisode" => $href,
                                "slugEps" => substr(strrchr($href, '/'), 1),
                            );
                            
                            // dd($SubListDetail);
                            return $SubListDetail; 
                        });

                    }else{
                        #Movie Detail
                        $SubListDetail= $crawler2->filter('.col-md-7')->each(function ($node,$i) {
                            $synopsis = $node->filter('.attachment-text')->text('Default text content');
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

                            $SubStatus = explode("<a",$DetailList[0][4][1]);
                            $status = '';
                            for($j=1;$j<count($SubStatus);$j++){
                                $statusField = substr($SubStatus[$j], strpos($SubStatus[$j], ">") + 1);
                                $status .= trim(str_replace('</a>','',$statusField));
                            }
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
                                "Judul" => $judul,
                                "JudulAlternatif" => $JudulAlternatif,
                                "Rating" => $Rating,
                                "Votes" => $Votes,
                                "Status" => $status,
                                "TotalEpisode"=> $TotalEpisode,
                                "HariTayang" => $HariTayang,

                            );
                            // $href = $node->filter('.attachment-block > a')->attr('href');
                            $SubListDetail = array(
                                "subDetail" => $SubDetail,
                                "synopsis" => $synopsis,
                                "genre" => $genre,
                                "hrefEpisode" => $hrefDetail,
                                "slugEps" => substr(strrchr($hrefDetail, '/'), 1),
                            );
                            // dd($SubListDetail);
                            return $SubListDetail; 
                        });
                    }
                    $SingleEpisode[]=array(
                        "SingleEpisode"=>$SubListDetail
                    );
                    
                }
                
                $dataPage = $crawler->filter('.col-md-12')->each(function ($node,$i) {
                    $DetailPageSpan = $node->filter('.page-numbers')->each(function ($node,$i) {
                        $hrefPageSpan = $node->filter('.page-numbers')->text('Default text content');
                        return $hrefPageSpan;
                    });
                    $detailPage = [
                        'pageSpanFilter' => $DetailPageSpan
                    ];
                    return $detailPage;
                });
                
                $valLastSpan = 0;
                foreach($dataPage[0]['pageSpanFilter'] as $key => $valSpanNew){
                    if(is_numeric($valSpanNew)){
                        if ($valSpanNew > $valLastSpan) {
                            $valLastSpan = $valSpanNew;
                        }
                    }
                }
                $TotalSearchPage = $valLastSpan;
                if(!is_numeric($TotalSearchPage)){
                    $TotalSearchPage = 1;
                }
                // dd($LastUpdateEps);
                if($PageNumber <= $TotalSearchPage){
                    $A =0;
                    for($i=0;$i < count($SingleEpisode);$i++){
                        $hrefEpisode = $SingleEpisode[$i]['SingleEpisode'][0]['hrefEpisode'];
                        $Image = $LastUpdateEps[0][$i]['image'];
                        $Title = $LastUpdateEps[0][$i]['title'];
                        $TitleAlias = $LastUpdateEps[0][$i]['titleAlias'];
                        $hrefSingleList = $LastUpdateEps[0][$i]['hrefSingleList'];
                        $slugDetail = $LastUpdateEps[0][$i]['slugDetail'];
                        $slugEpisode = $SingleEpisode[$i]['SingleEpisode'][0]['slugEps'];
                        
                        $Title = Converter::__normalizeNameEpsChar($Title,$hrefSingleList);
                        $TitleAlias = Converter::__normalizeNameEpsChar($TitleAlias,$hrefSingleList);
                        
                        if($Title == "Email"){
                            $Title = Converter::__normalizeNameEps($LastUpdateEps[0][$i]['hrefSingleList']);
                            $TitleAlias = Converter::__normalizeNameEps($LastUpdateEps[0][$i]['hrefSingleList']);
                        }
                        $TotalEpisode = $SingleEpisode[$i]['SingleEpisode'][0]['subDetail']['TotalEpisode'];
                        $Rating = $SingleEpisode[$i]['SingleEpisode'][0]['subDetail']['Rating'];
                        $Synopsis = $SingleEpisode[$i]['SingleEpisode'][0]['synopsis'];
                        $GenreList = $SingleEpisode[$i]['SingleEpisode'][0]['genre'];
                        $Years = '';
                        
                        $Status = $LastUpdateEps[0][$i]['status'];
                        $Episode = $LastUpdateEps[0][$i]['episode'];
                        $KeyEpisodeEnc=array(
                            "href" => $hrefEpisode,
                            "Episode" => $Episode
                        );
                        
                        $KeyEpisode = EnkripsiData::encodeKeyEpisodeAnime($KeyEpisodeEnc);
                        #jika data last update terdapat isi episode maka akan di save
                        if($slugEpisode){
                            {#save Data to mysql
                                $SlugEps = (Str::slug($slugEpisode));
                                $SlugDet = Str::slug($slugDetail);
                                $codeListEps['code'] = md5($SlugEps);
                                $codeDetailAnime['code'] = md5($SlugDet);
                                $codeListAnime['code'] = md5($SlugDet);
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "href"=>$hrefKeyListAnim[$i]
                                );
                                $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                
                                {#Save to List anime
                                    $listAnime = MainModel::getDataListAnime($codeListAnime);
                                    $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                    if(empty($listAnime) || $idListAnime == 0){
                                        if(empty($listAnime)){
                                            $Input = array(
                                                'code' => md5($SlugDet),
                                                'slug' => $SlugDet,
                                                'title' => $Title,
                                                'key_list_anime' => $KeyListAnim,
                                                'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                            );
                                            $save = MainModel::insertListAnimeMysql($Input);
                                        }else{
                                            $conditions['id'] = $idListAnime;
                                            $Update = array(
                                                'code' => md5($SlugDet),
                                                'slug' => $SlugDet,
                                                'title' => $Title,
                                                'key_list_anime' => $KeyListAnim,
                                                'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                            );
                                            $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                        }
                                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                    }
                                }#End Save to Liat anime
    
                                {#save to detail anime and list episode
                                    
                                    $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                                    $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                                    $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                                    $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                                    if(count($ListEpisodeAnime) > 0){
                                        foreach($ListEpisodeAnime as $valueEpisode){
                                            $checkDate = date("Y-m-d", strtotime($valueEpisode['cron_at']));
                                        }
                                    }
                                    
                                    if(empty($DetailAnime) || $idDetailAnime == 0 || $idListEpisode == 0 || 
                                    empty($ListEpisodeAnime) || $checkDate != date('Y-m-d')){
                                        $listDataAnime = [
                                            'params' => [
                                                'X-API-KEY' => env('X_API_KEY',''),
                                                'KeyListAnim' => $KeyListAnim
                                            ]
                                        ];
                                        
                                        $dataDetailAnime = $this->DetailListAnimeController->DetailListAnim(NULL,$listDataAnime);
                                    }
                                    $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                                    $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                                    $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                                    $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                                }#End #save to detail anime and list episode
                                {#save to Data Last Update
                                    $checkExist = MainModel::getDataLastUpdate($codeListEps);
                                    if(empty($checkExist)){
                                        $Input = array(
                                            'code' => md5($SlugEps),
                                            'slug' => $SlugEps,
                                            'id_list_anime' => $idListAnime,
                                            'id_list_episode' => $idListEpisode,
                                            'id_detail_anime' => $idDetailAnime,
                                            "image" => $Image,
                                            "title" => $Title,
                                            "title_alias" => $TitleAlias,
                                            "status" => $Status,
                                            "episode" => $Episode,
                                            "keyepisode" => $KeyEpisode,
                                            'total_search_page' => $TotalSearchPage,
                                            'page_search' => $PageNumber,
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        $LogSave [] = "Data Save - ".$SlugEps;
                                        $save  = MainModel::insertLastUpdateMysql($Input);
                                    }else{
                                        $conditions['id'] = $checkExist[0]['id'];
                                        $Update = array(
                                            'code' => md5($SlugEps),
                                            'slug' => $SlugEps,
                                            'id_list_anime' => $idListAnime,
                                            'id_list_episode' => $idListEpisode,
                                            'id_detail_anime' => $idDetailAnime,
                                            "image" => $Image,
                                            "title" => $Title,
                                            "title_alias" => $TitleAlias,
                                            "status" => $Status,
                                            "episode" => $Episode,
                                            "keyepisode" => $KeyEpisode,
                                            'total_search_page' => $TotalSearchPage,
                                            'page_search' => $PageNumber,
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        // if($i ==1){
                                        //     dd($Update);
                                        // }
                                        
                                        $LogSave [] =  "Data Update - ".$SlugEps.'-'.Carbon::now()->format('Y-m-d H:i:s');
                                        $save = MainModel::updateLastUpdateMysql($Update,$conditions);
                                        
                                    }
                                }#End save to Data Last Update
    
                            }#End save Data to mysql   
                        }else{
                            $LogSave [] =  "Data Episode Last Update Tidak ada di web - ".$Title;
                            $save = "Di web Kosong - ".$Title;
                        }
                        
                    }
                    return ResponseConnected::Success("Last Update Anime", $save, $LogSave, $awal);
                }else{
                    return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
                }
            }else{
                
                return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
            }
        }else{
            
            return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
        }
    }
    
    // ================ End  LastUpdateAnime Save To Mysql =========================================

    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [generateLastUpdateAnime]
     */
    // ================  generateLastUpdateAnime Save To Mongo =========================================
    public function generateLastUpdateAnime(Request $request = NULL, $params = NULL){
        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();

        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $code = (isset($param['params']['code']) ? $param['params']['code'] : '');
        $slug = (isset($param['params']['slug']) ? $param['params']['slug'] : '');
        $title = (isset($param['params']['title']) ? $param['params']['title'] : '');
        $startDate = (isset($param['params']['start_date']) ? $param['params']['start_date'] : NULL);
        $endDate = (isset($param['params']['end_date']) ? $param['params']['end_date'] : NULL);
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);

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
        
        $LastUpdate = MainModel::getDataLastUpdate($parameter);
        
        $errorCount = 0;
        $successCount = 0;
        if(count($LastUpdate)){
            foreach($LastUpdate as $LastUpdate){
                $conditions = [
                    'id_auto' => $LastUpdate['id'].'-lastUpdate',
                ];

                $MappingMongo = array(
                    'id_auto' => $LastUpdate['id'].'-lastUpdate',
                    'id_list_anime' => $LastUpdate['id_list_anime'],
                    'id_detail_anime' => $LastUpdate['id_detail_anime'],
                    'id_last_update' => $LastUpdate['id'],
                    'id_list_episode' => $LastUpdate['id_list_episode'],
                    'source_type' => 'lastUpdate-Anime',
                    'code' => $LastUpdate['code'],
                    'title' => Converter::__normalizeSummary($LastUpdate['title']),
                    'slug' => $LastUpdate['slug'],
                    'image' => $LastUpdate['image'],
                    'status' => $LastUpdate['status'],
                    'episode' => $LastUpdate['episode'],
                    'keyword' => explode('-',$LastUpdate['slug']),
                    'meta_title' => (Converter::__normalizeSummary(strtolower($LastUpdate['title']))),
                    'meta_keywords' => explode('-',$LastUpdate['slug']),
                    'meta_tags' => explode('-',$LastUpdate['slug']),
                    'cron_at' => $LastUpdate['cron_at']
                );
                
                $updateMongo = MainModelMongo::updateLastUpdateAnime($MappingMongo, $this->mongo['collections_last_update'], $conditions, TRUE);

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
    // ================ End generateLastUpdateAnime Save To Mysql =========================================

}