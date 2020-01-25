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

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;
use App\Helpers\V1\Converter as Converter;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

#Load Controller
use App\Http\Controllers\Nanime\DetailListAnimeController;

// done but masih proses debuging
class StreamAnimeController extends Controller
{
        public function __construct()
        {
            $this->DetailListAnimeController = new DetailListAnimeController();
        }
    // keyEpisode
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
            $Token = $Users[0]['token'];
            $NextEpisode = "";
            $PrevEpisode = "";
            if($Token){
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

        public function StreamValue($BASE_URL_LIST,$BASE_URL,$awal,$idDetailAnime_,$idListAnime_,$idListEpisode_){
            $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            // Connect a 2nd user using an isolated browser and say hi!
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();

            if($status == 200){
                // for get iframe from javascript
                try{
                    $cekServer =  $crawler->filter('#change-server')->html();
                }catch(\Exception $e){
                    $cekServer ="";
                }
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $synopsis = $node->filter('.description > p')->text('Default text content');
                    $Subgenre = $node->filter('.description')->html();
                    $detGenre = explode("<a", $Subgenre);
                    $genre=array();
                    for($j=1;$j<count($detGenre);$j++){
                        $genre[]=substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
                    } 
                    $ListDetail = $node->filter('.animeInfo > ul')->html();
                    $SubDetail01 = explode("<b", $ListDetail);
                    $Title = substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1);
                    $href =$node->filter('a')->attr('href');
                
                    $SubDetail02 = array(
                        "Title" => $Title,
                        "JudulAlternatif" => substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating" => substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes" => substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status" => substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode" => substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang" => substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),
                    );
                    $imageUrl = $node->filter('.col-md-3')->each(function ($node,$i) {
                        $ImgUrl = $node->filter('img')->attr('src');  
                        return $ImgUrl;
                    });    
                    
                    $SubListDetail=array(
                        "subDetail" => $SubDetail02,
                        "synopsis" => $synopsis,
                        "genre" => $genre,
                        "image" => $imageUrl
                        
                    );
                    return $SubListDetail; 
                });
                $SubMirror= $crawler->filter('#change-server')->each(function ($node,$i) {
                        $SubServer = $node->filter('option')->each(function ($node,$i) {
                            $NameServer = $node->filter('option')->text('Default text content');
                            $IframeSrc = $node->filter('option')->attr('value');   
                            $ListMirror = [
                                'NameServer' => $NameServer,
                                'IframeSrc'  => $IframeSrc
                            ];
                            
                            return $ListMirror;
                        });                        
                        return $SubServer;
                });
                $PaginationEpisode = $crawler->filter('.pagination')->each(function ($node,$i) {
                    $SubPaginationEpisode = $node->filter('a')->each(function ($node,$i) {
                        $hrefPaginationEps=$node->filter('a')->attr('href');
                        $TextPeginatEps=$node->filter('a')->text('Default text content');
                        $ListPegination=array(
                            "NamePegination" => $TextPeginatEps,
                            "hrefPegination" => $hrefPaginationEps
                        );
                        return $ListPegination;
                    });
                    return $SubPaginationEpisode;
                });
                
                if($cekServer){
                    {#List Property
                        $LinkNowEpisode=substr($BASE_URL_LIST, strrpos($BASE_URL_LIST, '-' )+1);
                        $NowEpisode=str_replace("/","",$LinkNowEpisode);
                        $Title = strtok($SubListDetail[0]['subDetail']['Title'],'<');
                        $Title = trim($Title);                        
                        $GenreList = str_replace("</a>","| ",implode($SubListDetail[0]['genre']));
                        $GenreList = trim($GenreList);
                        $Synopsis = trim($SubListDetail[0]['synopsis']);
                        $Tipe = "";
                        $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
                        $Years = "";
                        $Score = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                        $Rating = strtok($SubListDetail[0]['subDetail']['Rating'], '<');
                        $Studio = "";
                        $Duration = "";
                        $Episode = trim(strtok($SubListDetail[0]['subDetail']['TotalEpisode'], '<'));
                        $SlugEpisode = substr($BASE_URL_LIST, strrpos($BASE_URL_LIST, '/' )+1);
                        $SlugEpisode = str_replace("-00","-",$SlugEpisode);
                        $SlugEpisode = str_replace("-0","-",$SlugEpisode);
                        $TipeMovie = (strstr($BASE_URL_LIST,'episode')) ? "episode" : "movie";
                        $code = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
                        $SlugEpisode = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
                        
                        $imageUrl=$SubListDetail[0]['image'][0];
                        if(!empty($PaginationEpisode)){
                            $HrefPrev = $BASE_URL."".$PaginationEpisode[0][0]['hrefPegination'];
                            $HrefSingleList= $BASE_URL."".$PaginationEpisode[0][1]['hrefPegination'];
                            $HrefNext = $BASE_URL."".$PaginationEpisode[0][2]['hrefPegination'];
                        }else{
                            $HrefPrev = "";
                            $HrefSingleList= $BASE_URL_LIST;
                            $HrefNext = "";
                        }
                        
                        $KeyListAnimEnc= array(
                            "Title"=>$Title,
                            "Image"=>"",
                            "href"=>$HrefSingleList
                        );
                        
                        $href = $HrefSingleList;
                        
                        $Title = Converter::__normalizeTitle($Title,$href);
                        $NextEpisode = EnkripsiData::encodePaginationEps($HrefNext);
                        $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc); 
                        $PrevEpisode = EnkripsiData::encodePaginationEps($HrefPrev); 
                        if(empty($HrefPrev)){
                            $PrevEpisode="";
                        }
                        if(empty($HrefNext)){
                            $NextEpisode="";
                        }
                        $valueEps=str_replace("=","",$NowEpisode);
                        $valueEps=str_replace("episode","",$valueEps);
                        $filterValueEps=substr($valueEps, strpos($valueEps, '&') + 1);
                        $NowEpisode=$filterValueEps;
                    }#End List Property

                    {#cek query Relasi
                        {# Save List Anime
                            $cdListAnime = Str::slug($Title);
                            $codeListAnime['code'] = md5($cdListAnime);
                            $listAnime = MainModel::getDataListAnime($codeListAnime);
                            $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                            
                            if(empty($listAnime)|| $idListAnime == 0){
                                $idListAnime = $idListAnime_;
                                // $KeyListAnimEnc= array(
                                //     "Title"=>trim($Title),
                                //     "Image"=>$imageUrl,
                                //     "Type"=>trim($Tipe),
                                //     "href"=>$BASE_URL_LIST
                                // );
                                // if(empty($listAnime)){
                                //     $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                //     $Input = array(
                                //         'code' => md5(Str::slug($Title)),
                                //         'slug' => Str::slug($Title),
                                //         'title' => $Title,
                                //         'key_list_anime' => $KeyListAnim,
                                //         'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                //         'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                //     );
                                //     $save = MainModel::insertListAnimeMysql($Input);
                                // }else{
                                //     $conditions['id'] = $idListAnime;
                                //     $Update = array(
                                //         'code' => md5(Str::slug($Title)),
                                //         'slug' => Str::slug($Title),
                                //         'title' => $Title,
                                //         'key_list_anime' => $KeyListAnim,
                                //         'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                //         'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                //     );
                                //     $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                // }
                                // $codeListAnime['code'] = md5(Str::slug($Title));
                                // $listAnime = MainModel::getDataListAnime($codeListAnime);
                                // $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                            }
                        }#End List Anime
                        
                        {# to detail anime and list episode
                            $codeListEps['code'] = md5($SlugEpisode);
                            $codeDetailAnime['code'] = md5($cdListAnime);
                            $SlugDetailAnime = $cdListAnime;
                            $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                            $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                            $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                            $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                            
                            if(empty($DetailAnime) || $idDetailAnime == 0 || $idListEpisode == 0 || empty($ListEpisodeAnime) ){
                                $idDetailAnime = $idDetailAnime_;
                                $idListEpisode = $idListEpisode_;
                                // $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                // $listDataAnime = [
                                //     'params' => [
                                //         'X-API-KEY' => env('X_API_KEY',''),
                                //         'KeyListAnim' => $KeyListAnim
                                //     ]
                                // ];
                                // $dataDetailAnime = $this->DetailListAnimeController->DetailListAnim(NULL,$listDataAnime);
                                // $codeDetailAnime['code'] = md5($cdListAnime);
                                
                            }
                            
                        }#End # to detail anime and list episode

                        {#save to StreamAnime
                            $paramCheck['code'] = md5($code);
                            $checkExist = MainModel::getStreamAnime($paramCheck);
                            if(empty($checkExist)){
                                $Input = array(
                                    'code' => md5($code),
                                    'slug' => $SlugEpisode,
                                    'title' => $Title,
                                    'id_list_anime' => $idListAnime,
                                    'id_list_episode' => $idListEpisode,
                                    'id_detail_anime' => $idDetailAnime,
                                    'next_episode' => $NextEpisode,
                                    'key_list_anime' => $KeyListAnim,
                                    'prev_episode' => $PrevEpisode,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $save = MainModel::insertStreamAnimeMysql($Input);
                                $checkExist = MainModel::getStreamAnime($paramCheck);
                                $LogSave = $this->saveServerStream($SubMirror,$checkExist,$Title);
            
                            }else{
                                $conditions['id'] = $checkExist[0]['id'];
                                $Update = array(
                                    'code' => md5($code),
                                    'slug' => $SlugEpisode,
                                    'title' => $Title,
                                    'id_list_anime' => $idListAnime,
                                    'id_list_episode' => $idListEpisode,
                                    'id_detail_anime' => $idDetailAnime,
                                    'next_episode' => $NextEpisode,
                                    'key_list_anime' => $KeyListAnim,
                                    'prev_episode' => $PrevEpisode,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $save = MainModel::updateStreamAnimeMysql($Update,$conditions);
                                $LogSave = $this->saveServerStream($SubMirror,$checkExist,$Title);
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

        public static function saveServerStream($SubMirror,$checkExist,$Title){
            $ListServer = array();
            $idStreamAnime= $checkExist[0]['id'];
            $LogSave = array();
            for($i=1;$i<count($SubMirror[0]);$i++){
                $NameServer = trim($SubMirror[0][$i]['NameServer']);
                $IframeSrc = ($SubMirror[0][$i]['IframeSrc']);
                
                $paramCheck['code'] = md5(Str::slug($NameServer));
                $paramCheck['id_stream_anime'] = $idStreamAnime;
                $checkExist1 = MainModel::getServerStream($paramCheck);
                $code = Str::slug($NameServer);
                
                if(empty($checkExist1)){
                    $Input = array(
                        'code' => md5($code),
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
                        'code' => md5($code),
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


        public function FilterIframe($value){
            $valueOnclick = str_replace("changeDivContent","",$value);
            $filterValue = substr($valueOnclick, strpos($valueOnclick, '"') + 1);
            $iframe = strtok($filterValue, '"');
            return $iframe;
        }

        public function ReverseStrrchr($haystack, $needle)
        {
            $pos = strrpos($haystack, $needle);
            if($pos === false) {
                return $haystack;
            }
            return substr($haystack, 0, $pos + 1);
        }
}