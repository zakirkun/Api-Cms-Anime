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
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Models V1
use App\Models\V1\MainModel as MainModel;
use App\Models\V1\Mongo\MainModelMongo as MainModelMongo;

// done
class DetailListAnimeController extends Controller
{
    function __construct(){
        $this->mongo = Config::get('mongo');
    }
    /**
     * @author [prayugo]
     * @create date 2020-01-26 18:19:09
     * @desc [DetailListAnime]
     */
    // ================================== DetailListAnime Save to Mysql =========================
    public function DetailListAnim(Request $request = NULL, $params = NULL){
        $awal = microtime(true);
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
            $KeyListAnim = $request->header("KeyListAnim");
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
            $KeyListAnim = (isset($params['params']['KeyListAnim']) ? ($params['params']['KeyListAnim']) : '');
        }
        $Users = MainModel::getUser($ApiKey);
        // $Token = $Users[0]['token'];
        
        if(!empty($Users)){
            // try{
                $findCode = strstr($KeyListAnim,'QWTyu');
                $KeyListDecode = EnkripsiData::DecodeKeylistAnime($KeyListAnim);
                
                if($findCode){
                    if($KeyListDecode){
                        $subHref=$KeyListDecode->href;
                        $ConfigController = new ConfigController();
                        $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                        $BASE_URL_LIST=$subHref;
                        return $this->SingleListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal);
                    }else{
                        return ResponseConnected::InvalidKey("Detail Anime","Invalid Key", $awal);
                    }                
                }else{
                    return ResponseConnected::InvalidKey("Detail Anime","Invalid Key", $awal);
                }
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Detail Anime","Internal Server Error");
            // }
        }else{
            return ResponseConnected::InvalidToken("Detail Anime","Invalid Token", $awal);
        }
    }
    public function FilterHreftEpisode($value){
        $subHref = explode("<a", $value);
        $valueHref =str_replace("href","",$subHref[1]);
        $filterValue = substr($valueHref, strpos($valueHref, '"') + 1);
        $href = strtok($filterValue, '"');
        return $href;
    }
    public function FilterHreftDetail($DetailList){
        $subHrefDetail = explode("<a",$DetailList);
        $substring = substr($subHrefDetail[1], 0, strpos($subHrefDetail[1], '>'));
        $hrefDetField = substr($substring, strpos($substring, "f") + 1);
        $hrefDetField = str_replace('=','',$hrefDetField);
        return $hrefDetField;
    }
    
    public function SingleListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        // $crawler = $goutteClient->request('GET', 'https://nanime.yt/movie/orange-live-action');
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            try{
                $DetailHref =  $crawler->filter('#table-episode')->html();
            }catch(\Exception $e){
                $DetailHref ="";
            }
            
            if($DetailHref){
                #Detail Episode
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
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
                    
                    // dd($DetailList);
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
                        "Status" => ($status == '') ? 'Movie': $status,
                        "TotalEpisode"=> $TotalEpisode,
                        "HariTayang" => $HariTayang,

                    );
                    $EpisodeList = $DetailList[1];
                    
                    $EpisodeList02 = array();
                    $j = count($EpisodeList)-1;
                    for($i = 0 ; $i < count($EpisodeList) ;$i++){
                        $EpisodeList02[$j] = self::FilterHreftEpisode($EpisodeList[$i][0]);
                        $j--;
                    }
                    $DataEps = array();
                    for($i = 0 ; $i < count($EpisodeList02) ;$i++){
                        $hrefEps = ($EpisodeList02[$i]);
                        $NameEps = ucwords(str_replace('-',' ',substr(strrchr($hrefEps, '/'), 1)));
                        $DataEps[0][$i] = array(
                            'href' => $hrefEps,
                            'nameEps' => $NameEps,
                            'slugEps'=> substr(strrchr($hrefEps, '/'), 1),
                        );
                    }
                    
                    $slugDetail = substr(strrchr($hrefDetail, '/'), 1);
                    
                    $SubListDetail=array(
                        "slugDetail" => $slugDetail,
                        "subDetail" => $SubDetail,
                        "synopsis" => $synopsis,
                        "image" => $imageUrl,
                        "genre" => $genre,
                        "DataEps" => $DataEps
                    );
                    
                    return $SubListDetail; 
                });
            }else{
                #Detail Movie
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
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
                        "Status" => ($status == '') ? 'Movie': $status,
                        "TotalEpisode"=> $TotalEpisode,
                        "HariTayang" => $HariTayang,
                    );
                    
                    $hrefEps = $hrefDetail;
                    $NameEps = ucwords(str_replace('-',' ',substr(strrchr($hrefEps, '/'), 1)));
                    $DataEps[0][0]=array(
                        'href' => $hrefEps,
                        'nameEps'=> $NameEps,
                        "slugEps" => substr(strrchr($hrefEps, '/'), 1),
                    );
                    $slugDetail = substr(strrchr($hrefDetail, '/'), 1);
                    $SubListDetail=array(
                        "slugDetail" => $slugDetail,
                        "subDetail" => $SubDetail,
                        "synopsis" => $synopsis,
                        "image" => $imageUrl,
                        "genre" => $genre,
                        "DataEps" => $DataEps
                    );
                    
                    return $SubListDetail; 
                });
                
            }
            
            // Get the latest post in this category and display the titles
            if($SubListDetail){
                $genree = "";
                $Title = trim(strtok($SubListDetail[0]['subDetail']['Title'],'<'));
                $Title = Converter::__normalizeTitle($Title,$BASE_URL_LIST);
                if($Title == "Email"){
                    $Title = Converter::__normalizeNameEps($BASE_URL_LIST);
                }
                $SlugDet = trim($SubListDetail[0]['slugDetail']);
                $Synopsis = trim($SubListDetail[0]['synopsis']);
                $SubGenre =  $SubListDetail[0]['genre'];
                for($i = 0 ; $i < count($SubGenre) ; $i++){
                    $genree .= strtok($SubListDetail[0]['genre'][$i],'<').'| ';
                }
                
                $Tipe = "";
                $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
                
                $Votes = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                $Years = "";
                $Score = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                $Rating = strtok($SubListDetail[0]['subDetail']['Rating'], '<');
                $Studio = "";
                $Episode = strtok($SubListDetail[0]['subDetail']['TotalEpisode'], '<');
                $HariTayang = strtok($SubListDetail[0]['subDetail']['HariTayang'], '<');
                $Duration = "";
                $GenreList = rtrim($genree,"|");
                $imageUrl = $SubListDetail[0]['image'];

                {#Save To Mysql
                    $SlugDet = Str::slug($SlugDet);
                    $codeDetailAnime['code'] = md5(($SlugDet));
                    $codeListAnime['code'] = md5($SlugDet);

                    {#save To list Anime
                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                        $KeyListAnimEnc = array(
                            "Title"=>trim($Title),
                            "href"=>$BASE_URL_LIST
                        );
                        $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                        
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
                        }
                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                    }#End save To list Anime

                    {#save to Detail Anime
                        $checkExist = MainModel::getDataDetailAnime($codeDetailAnime);
                        $LogSave = array();
                        if(empty($checkExist)){
                            $Input = array(
                                'code' => md5($SlugDet),
                                'slug' => ($SlugDet),
                                'title' => $Title,
                                'image' => $imageUrl,
                                'tipe' => $Tipe,
                                'genre' => $GenreList,
                                'status' => $Status,
                                'hari_tayang' => $HariTayang,
                                'episode_total' => $Episode,
                                'years' => $Years,
                                "votes" => $Votes,
                                'score' => $Score,
                                'rating' => $Rating,
                                'studio' => $Studio,
                                'duration' => $Duration,
                                'synopsis' => trim($Synopsis),
                                'id_list_anime' => $idListAnime,
                                
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            // dd($Input);
                            $save = MainModel::insertDetailMysql($Input);
                            $detailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                            $idDetailAnime = (empty($detailAnime)) ? 0 : $detailAnime[0]['id'];
                            if(count($SubListDetail[0]['DataEps']) > 0 ){
                                $LogSave = $this->saveListEpisode($SubListDetail,$idDetailAnime,$idListAnime,$Title,$BASE_URL);
                            }else{
                                $LogSave = 'Tidak ada Episode Yang di simpan hanya data detail yang di simpan';
                            }
                            
        
                        }else{
                            $conditions['id'] = $checkExist[0]['id'];
                            $Update = array(
                                'code' => md5($SlugDet),
                                'slug' => $SlugDet,
                                'title' => $Title,
                                'image' => $imageUrl,
                                'tipe' => $Tipe,
                                'genre' => $GenreList,
                                'status' => $Status,
                                'hari_tayang' => $HariTayang,
                                'episode_total' => $Episode,
                                'years' => $Years,
                                'score' => $Score,
                                "votes" => $Votes,
                                'rating' => $Rating,
                                'studio' => $Studio,
                                'duration' => $Duration,
                                'synopsis' => trim($Synopsis),
                                'id_list_anime' => $idListAnime,
                                
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            // dd($Update);
                            $save = MainModel::updateDetailMysql($Update,$conditions);
                            $detailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                            $idDetailAnime = (empty($detailAnime)) ? 0 : $detailAnime[0]['id'];
                            
                            if(count($SubListDetail[0]['DataEps']) > 0 ){
                                $LogSave = $this->saveListEpisode($SubListDetail,$idDetailAnime,$idListAnime,$Title,$BASE_URL);
                            }else{
                                $LogSave = 'Tidak ada Episode Yang di simpan hanya data detail yang di simpan';
                            }
                            
                        }
                    }#End save to Detail Anime

                }#End Save To Mysql
                return ResponseConnected::Success("Detail Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("Detail Anime","Page Not Found.", $awal);
            }
        }else{
            return ResponseConnected::PageNotFound("Detail Anime","Page Not Found.", $awal);
        }
    }

    public static function saveListEpisode($SubListDetail,$idDetailAnime,$idListAnime,$Title,$BASE_URL){
        $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
        $ListEpisode = array();
        $imageUrl = $SubListDetail[0]['image'];
        for($i=0;$i<count($SubListDetail[0]['DataEps'][0]);$i++){
            $KeyEpisodeEnc = array(
                "href"=>$SubListDetail[0]['DataEps'][0][$i]['href'],
                "Episode"=>$SubListDetail[0]['DataEps'][0][$i]['nameEps'],
            );
            
            
            $KeyEpisode = EnkripsiData::encodeKeyEpisodeAnime($KeyEpisodeEnc);
            $SlugEpisode = (Str::slug($SubListDetail[0]['DataEps'][0][$i]['slugEps']));
            $hrefEpisode = $SubListDetail[0]['DataEps'][0][$i]['href'];
            $Episode = Converter::__normalizeNameEps($hrefEpisode);
            // $SlugEpisode = Str::slug($Episode);

            // $TipeMovie = (strstr($hrefEpisode,'episode')) ? "episode" : "movie";
            // $code = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
            // $SlugEpisode = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
            
            $idListEpisode['code'] = md5($SlugEpisode);
            $checkExist = MainModel::getDataListEpisodeAnime($idListEpisode);
            if(empty($checkExist)){
                $Input = array(
                    'code' => md5($SlugEpisode),
                    'slug' => $SlugEpisode,
                    "episode" => $Episode,
                    'key_episode' => $KeyEpisode,
                    'id_list_anime' => $idListAnime,
                    'id_detail_anime' => $idDetailAnime,
                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                );
                $LogSave [] = "Data Save - ".$Episode."-".$Title;
                $save = MainModel::insertListEpisodelMysql($Input);
            }else{
                $conditions['id'] = $checkExist[0]['id'];
                $Update = array(
                    'code' => md5($SlugEpisode),
                    'slug' => $SlugEpisode,
                    "episode" => $Episode,
                    'key_episode' => $KeyEpisode,
                    'id_list_anime' => $idListAnime,
                    'id_detail_anime' => $idDetailAnime,
                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                );
                $LogSave [] =  "Data Update - ".$Episode."-".$Title;
                $save = MainModel::updateListEpisodeMysql($Update,$conditions);
            }
        
        }
        return $LogSave;
    }
    // ================================== End DetailListAnime Save to Mysql =========================

    /**
     * @author [prayugo]
     * @create date 2020-01-26 18:19:09
     * @desc [generateDetailAnime]
     */
    // ================================== generateDetailAnime Save to Mongo =========================
    public function generateDetailAnime(Request $request = NULL, $params = NULL){

        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();
        
        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $idListAnime = (isset($param['params']['id_list_anime']) ? $param['params']['id_list_anime'] : NULL);
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
            'code' => $code,
            'slug' => $slug,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_updated' => $isUpdated
        ];
        $detailAnime = MainModel::getDataDetailAnime($parameter);
        
        $errorCount = 0;
        $successCount = 0;
        if(count($detailAnime)){
            foreach($detailAnime as $detailAnime){
                $conditions = [
                    'id_auto' => $detailAnime['id'].'-detailAnime',
                ];
                $param = [
                    'id_detail_anime' => $detailAnime['id']
                ];
                $ListEpisode = MainModel::getDataListEpisodeJoin($param);
                $dataEps = array();
                foreach($ListEpisode as $ListEpisode){
                    $dataEps[] = array(
                        'id_episode' => $ListEpisode['id_list_episode'],
                        'id_stream_anime' => $ListEpisode['id_stream_anime'],
                        'slug' => $ListEpisode['slug'],
                        'episode' => $ListEpisode['episode']
                    );
                }
                 
                $MappingMongo = array(
                    'id_auto' => $detailAnime['id'].'-detailAnime',
                    'id_list_anime' => $detailAnime['id_list_anime'],
                    'id_detail_anime' => $detailAnime['id'],
                    'source_type' => 'detail-Anime',
                    'code' => $detailAnime['code'],
                    'title' => Converter::__normalizeSummary($detailAnime['title']),
                    'slug' => $detailAnime['slug'],
                    'type' => $detailAnime['tipe'],
                    'synopsis' => $detailAnime['synopsis'],
                    'episode' => $dataEps,
                    'image' => $detailAnime['image'],
                    'status' => $detailAnime['status'],
                    'episode_total' => $detailAnime['episode_total'],
                    'score' => $detailAnime['score'],
                    'rating' => $detailAnime['rating'],
                    'studio' => $detailAnime['studio'],
                    'hari_tayang' => $detailAnime['hari_tayang'],
                    'duration' => $detailAnime['duration'],
                    'genre' => explode('|',substr(trim($detailAnime['genre']),0,-1)),
                    'keyword' => explode('-',$detailAnime['slug']),
                    'meta_title' => (Converter::__normalizeSummary(strtolower($detailAnime['title']))),
                    'meta_keywords' => explode('-',$detailAnime['slug']),
                    'meta_tags' => explode('-',$detailAnime['slug']),
                    'cron_at' => $detailAnime['cron_at']
                );
                $updateMongo = MainModelMongo::updateDetailListAnime($MappingMongo, $this->mongo['collections_detail_anime'], $conditions, TRUE);
                
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
                        $error_id['response']['id'][$key] = $detailAnime['id']; #set id error generate
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
    // ================================== End generateDetailAnime Save to Mysql =========================


    
}