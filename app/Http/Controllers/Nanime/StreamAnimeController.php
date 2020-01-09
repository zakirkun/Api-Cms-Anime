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

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done but masih proses debuging
class StreamAnimeController extends Controller
{
    // keyEpisode
        public function StreamAnime(Request $request = NULL, $params = NULL){
            $awal = microtime(true);
            if(!empty($request) || $request != NULL){
                $ApiKey = $request->header("X-API-KEY");
                $KeyEpisode = $request->header("KeyEpisode");
            }
            if(!empty($params) || $params != NULL){
                $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
                $KeyEpisode = (isset($params['params']['KeyEpisode']) ? ($params['params']['KeyEpisode']) : '');
            }
            $Users = MainModel::getUser($ApiKey);
            $Token = $Users[0]['token'];
            $NextEpisode = "";
            $PrevEpisode = "";
            if($Token){
                // try{
                    $findCode=strstr($KeyEpisode,'QtYWL');
                    $KeyListDecode= $this->DecodeKeyListAnim($KeyEpisode);
                    if($findCode){
                        if($KeyListDecode){
                            $subHref=$KeyListDecode->href;
                            $ConfigController = new ConfigController();
                            $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                            if($NextEpisode){
                                $findCode=strstr($NextEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = $this->DecodePaginationEps($NextEpisode);
                                    $URL_Next=$KeyPagiDecode->href;
                                    $BASE_URL_LIST=$URL_Next;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal);
                                }else{
                                    return $this->InvalidKeyPagination();
                                }
                            }elseif($PrevEpisode){
                                $findCode=strstr($PrevEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = $this->DecodePaginationEps($PrevEpisode);
                                    $URL_PREV=$KeyPagiDecode->href;
                                    $BASE_URL_LIST=$URL_PREV;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal);
                                }else{
                                    return $this->InvalidKeyPagination();
                                }
                            }else{
                                $BASE_URL_LIST=$subHref;
                                return $this->StreamValue($BASE_URL_LIST,$BASE_URL,$awal);
                            }
                        }else{
                            return $this->InvalidKey();
                        }
                        
                    }else{
                        return $this->InvalidKey();
                    }

                // }catch(\Exception $e){
                //     return $this->InternalServerError();
                // }
                
            }else{
                return $this->InvalidToken();
            }
        }

        public function StreamValue($BASE_URL_LIST,$BASE_URL,$awal){
            
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
                    $SubDetail02 = array(
                        "Title" => substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
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
                        $NextEpisode = $this->EncriptPaginationEps($HrefNext);
                        $KeyListAnim = $this->EncriptKeyListAnim(trim($Title),$HrefSingleList); 
                        $PrevEpisode = $this->EncriptPaginationEps($HrefPrev); 
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
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>$imageUrl,
                                    "Type"=>trim($Tipe),
                                    "href"=>$BASE_URL_LIST
                                );
                                if(empty($listAnime)){
                                    $KeyListAnim = self::encodeKeyListAnime($KeyListAnimEnc);
                                    $Input = array(
                                        'code' => md5(Str::slug($Title)),
                                        'slug' => Str::slug($Title),
                                        'title' => $Title,
                                        'key_list_anime' => $KeyListAnim,
                                        'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $save = MainModel::insertListAnimeMysql($Input);
                                }else{
                                    $conditions['id'] = $idListAnime;
                                    $Update = array(
                                        'code' => md5(Str::slug($Title)),
                                        'slug' => Str::slug($Title),
                                        'title' => $Title,
                                        'key_list_anime' => $KeyListAnim,
                                        'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                }
                                $codeListAnime['code'] = md5(Str::slug($Title));
                                $listAnime = MainModel::getDataListAnime($codeListAnime);
                                $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                            }
                        }#End List Anime
                        
                        {#save to detail anime and list episode
                            
                            $codeListEps['code'] = md5($SlugEpisode);
                            $codeDetailAnime['code'] = md5($cdListAnime);
                            $SlugDetailAnime = $cdListAnime;
                            $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                            $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                            $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                            $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                            if(empty($DetailAnime) || $idDetailAnime == 0 || $idListEpisode == 0 || empty($ListEpisodeAnime) ){
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>"",
                                    "href"=>$hrefKeyListAnim[$i]
                                );
                                $KeyListAnim = self::encodeKeyListAnime($KeyListAnimEnc);
                                $listDataAnime = [
                                    'params' => [
                                        'X-API-KEY' => env('X_API_KEY',''),
                                        'KeyListAnim' => $KeyListAnim
                                    ]
                                ];
                                $dataDetailAnime = $this->DetailListAnimeController->DetailListAnim(NULL,$listDataAnime);
                                $codeDetailAnime['code'] = md5($cdListAnime);
                                
                            }
                            
                            $codeListEps['code'] = md5($SlugEpisode);
                            $codeDetailAnime['code'] = md5($cdListAnime);
                            $SlugDetailAnime = $cdListAnime;
                            $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                            $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                            $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                            $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                        }#End #save to detail anime and list episode

                        {#Save Last Update
                            // $codeLastUpdate['code'] = md5($code);
                            // $lastUpdate = MainModel::getDataLastUpdate($codeLastUpdate);
                            // $idLastUpdate = (empty($lastUpdate)) ? 0 : $lastUpdate[0]['id'];
                            // if(empty($lastUpdate) || $idLastUpdate == 0){
                            //     $KeyEpisodeEnc=array(
                            //         "href" => $BASE_URL_LIST,
                            //         "Image" => $imageUrl,
                            //         "Title" => $Title,
                            //         "Status" => $Status,
                            //         "Episode" => $NowEpisode
                            //     );
                            //     $KeyEpisode = self::encodeKeyListEpisode($KeyEpisodeEnc);
                            //     if(empty($lastUpdate)){
                            //         $Input = array(
                            //             "image" => $imageUrl,
                            //             "title" => $Title,
                            //             "title_alias" => $Title,
                            //             "status" => $Status,
                            //             "episode" => $NowEpisode,
                            //             "keyepisode" => $KeyEpisode,
                            //             'total_search_page' => "",
                            //             'page_search' => "",
                            //             'slug' => $SlugEpisode,
                            //             'id_list_anime' => $idListAnime,
                            //             'code' => md5($code),
                            //             'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            //         );
                            //         $save = MainModel::insertLastUpdateMysql($Input);
                            //     }else{
                            //         $conditions['id'] = $idLastUpdate;
                            //         $Update = array(
                            //             "image" => $imageUrl,
                            //             "title" => $Title,
                            //             "title_alias" => $Title,
                            //             "status" => $Status,
                            //             "episode" => $NowEpisode,
                            //             "keyepisode" => $KeyEpisode,
                            //             'total_search_page' => "",
                            //             'page_search' => "",
                            //             'slug' => $SlugEpisode,
                            //             'id_list_anime' => $idListAnime,
                            //             'code' => md5($code),
                            //             'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            //         );
                            //         $save = MainModel::updateLastUpdateMysql($Update,$conditions);
                            //     }
                            //     $codeLastUpdate['code'] = md5($code);
                            //     $lastUpdate = MainModel::getDataLastUpdate($codeLastUpdate);
                            //     $idLastUpdate = (empty($lastUpdate)) ? 0 : $lastUpdate[0]['id'];
                                
                            // }
                        }#End Last Update

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

                    return $this->Success($save,$LogSave,$awal);
                }else{
                    return $this->PageNotFound();
                }
            }else{
                return $this->PageNotFound();
            }
        }

        public static function saveServerStream($SubMirror,$checkExist,$Title){
            $ListServer = array();
            $idStreamAnime= $checkExist[0]['id'];
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

        public static function encodeKeyListAnime($KeyListAnimEnc){
            $result = base64_encode(json_encode($KeyListAnimEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "QWTyu" . $iduniq1;
            $KeyListAnim = $result;
            return $KeyListAnim;
        }

        public static function encodeKeyListEpisode($KeyEpisodeEnc){
            $result = base64_encode(json_encode($KeyEpisodeEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "QtYWL" . $iduniq1;
            $KeyEpisode = $result;
            return $KeyEpisode;
        }

        public function Success($save,$LogSave,$awal){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Success.",
                        "Speed" => self::SpeedResponse($awal),
                        "Code" => 200
                    ),
                    "LogBody"=> array(
                        "DataLog"=>$LogSave
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InvalidKeyPagination(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Key Pagination",
                        "Code" => 401
                    ),
                    "Body"=> array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InvalidKey(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Key",
                        "Code" => 401
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function PageNotFound(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Page Not Found",
                        "Code" => 404
                    ),
                    "Body" => array(
                        "StreamAnime" =>array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InternalServerError(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Internal Server Error",
                        "Code" => 500
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }

        public function InvalidToken(){
            $API_TheMovie = array(
                "API_TheMovieRs" => array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" => "Stream Anime",
                    "Status" => "Not Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Token",
                        "Code" => 203
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }

        public function FilterIframe($value){
            $valueOnclick = str_replace("changeDivContent","",$value);
            $filterValue = substr($valueOnclick, strpos($valueOnclick, '"') + 1);
            $iframe = strtok($filterValue, '"');
            return $iframe;
        }

        public function DecodePaginationEps($KeyPagination){
            $decode = str_replace('QRCAbuK', "=", $KeyPagination);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('MTrU', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            return $KeyListDecode;
        }

        public function DecodeKeyListAnim($KeyEpisode){
            $decode = str_replace('QRCAbuK', "=", $KeyEpisode);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('QtYWL', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            return $KeyListDecode;
        }

        public function EncriptPaginationEps($ListEncript){
            $KeyPegiAnimEnc= array(
                "Title"=>"",
                "Image"=>"",
                "href"=>$ListEncript
            );
            $result = base64_encode(json_encode($KeyPegiAnimEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "MTrU" . $iduniq1;
            $KeyEncript = $result;

            return $KeyEncript;
        }

        public function EncriptKeyListAnim($Title,$ListEncript){
            $KeyListAnimEnc= array(
                "Title"=>$Title,
                "Image"=>"",
                "href"=>$ListEncript
            );
            $result = base64_encode(json_encode($KeyListAnimEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "QWTyu" . $iduniq1;
            $KeyEncript = $result;

            return $KeyEncript;
        }

        public function ReverseStrrchr($haystack, $needle)
        {
            $pos = strrpos($haystack, $needle);
            if($pos === false) {
                return $haystack;
            }
            return substr($haystack, 0, $pos + 1);
        }

        public static function SpeedResponse($awal){
            $akhir = microtime(true);
            $durasi = $akhir - $awal;
            $jam = (int)($durasi/60/60);
            $menit = (int)($durasi/60) - $jam*60;
            $detik = $durasi - $jam*60*60 - $menit*60;
            return $kecepatan = number_format((float)$detik, 2, '.', '');
        }
}