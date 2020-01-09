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

#Load Helper V1
use App\Helpers\V1\Converter as Converter;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class DetailListAnimeController extends Controller
{
    // KeyListAnim
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
        $Token = $Users[0]['token'];
        
        if($Token){
            // try{
                $findCode=strstr($KeyListAnim,'QWTyu');
                $KeyListDecode=$this->DecodeKeylistAnime($KeyListAnim);
                if($findCode){
                    if($KeyListDecode){
                        $subHref=$KeyListDecode->href;
                        $ConfigController = new ConfigController();
                        $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                        $BASE_URL_LIST=$subHref;
                        return $this->SingleListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal);
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
    public function InternalServerError(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Internal Server Error",
                    "Code" => 500
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }

    public function Success($save,$LogSave,$awal){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
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
    public function InvalidKey(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Invalid Key",
                    "Code" => 401
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }
    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Page Not Found.",
                    "Code" => 404
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }
    public function InvalidToken(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Invalid Token",
                    "Code" => 203
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }
    public function DecodeKeylistAnime($KeyListAnim){
        $decode = str_replace('QRCAbuK', "=", $KeyListAnim);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10,500);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('QWTyu', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public function SingleListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            try{
                $DetailHref =  $crawler->filter('.col-md-12 > .episodelist')->html();
            }catch(\Exception $e){
                $DetailHref ="";
            }
            
            if($DetailHref){
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $synopsis = $node->filter('.description > p')->text('Default text content');
                    $Subgenre = $node->filter('.description')->html();
                    $imageUrl = $node->filter('.img-responsive')->attr("src");
                    $detGenre = explode("<a", $Subgenre);
                    $genre=array();
                    for($j=1;$j<count($detGenre);$j++){
                        $genre[]=substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
                    } 
                    $ListDetail = $node->filter('.animeInfo > ul')->html();
                    $SubDetail01 = explode("<b", $ListDetail);
                    $deleteEmail = ['[','email','protected',']',',','@'];
                    if (stripos((Converter::__normalizeSummary(substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1))),'[email') !== false) {
                        $Title = "Email";    
                    }else{
                        $Title = substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1);
                    }
                    
                    $SubDetail02=array(
                        "Title"=>$Title,
                        "JudulAlternatif"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating"=>substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes"=>substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status"=>substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode"=>substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang"=>substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),

                    );
                    $DataEps =  $node->filter('.episodelist')->each(function ($node,$i) {
                        $SubDataEps =  $node->filter('a')->each(function ($node,$i) {
                            $hrefEps = $node->filter('a')->attr('href');
                            $NameEps = $node->filter('a')->text('Default text content');
                            if (stripos((Converter::__normalizeSummary($NameEps)),'[email') !== false) {
                                $NameEps = substr($hrefEps, strrpos($hrefEps, '/' )+1);
                                $NameEps = str_replace("-00","-",$NameEps);
                                $NameEps = str_replace("-0","-",$NameEps);
                                $NameEps = str_replace("-"," ",$NameEps);
                            }
                            $SubListDetail=array(
                                'href' => $hrefEps,
                                'nameEps'=>$NameEps,
                            );
                            return $SubListDetail; 
                        });
                        return $SubDataEps; 
                    });
                    
                    $SubListDetail=array(
                        "subDetail"=>$SubDetail02,
                        "synopsis"=>$synopsis,
                        "image"=>$imageUrl,
                        "genre"=>$genre,
                        "DataEps"=>$DataEps
                    );
                    return $SubListDetail; 
                });
            }else{
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
                    $deleteEmail = ['[','email','protected',']',',','@'];
                    if (stripos((Converter::__normalizeSummary(substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1))),'[email') !== false) {
                        $Title = "Email";    
                    }else{
                        $Title = substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1);
                    }
                    
                    $SubDetail02=array(
                        "Title"=>$Title,
                        "JudulAlternatif"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating"=>substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes"=>substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status"=>substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode"=>substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang"=>substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),

                    );
                    
                    $href = $node->filter('.col-md-3 > a')->attr("href");
                    $imageUrl = $node->filter('.col-md-3 > a > img')->attr("src");
                    $NameEps = substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1);
                    if (stripos((Converter::__normalizeSummary($NameEps)),'[email') !== false) {
                        $NameEps = substr($hrefEps, strrpos($hrefEps, '/' )+1);
                        $NameEps = str_replace("-00","-",$NameEps);
                        $NameEps = str_replace("-0","-",$NameEps);
                        $NameEps = str_replace("-"," ",$NameEps);
                    }
                    $DataEps[0][0]=array(
                        'href' => $href,
                        'nameEps'=>$NameEps,
                    );
                    $SubListDetail=array(
                        "subDetail"=>$SubDetail02,
                        "synopsis"=>$synopsis,
                        "image"=>$imageUrl,
                        "genre"=>$genre,
                        "DataEps"=>$DataEps
                    );
                    return $SubListDetail; 
                });
                
            }
            
            // Get the latest post in this category and display the titles
            
            if($SubListDetail){
                $genree="";
                $Title = trim(strtok($SubListDetail[0]['subDetail']['Title'],'<'));
                $Title = str_replace('&amp;','',$Title);
                if($Title == "Email"){
                    $Title = self::filterCodeDetailAnime($BASE_URL_LIST);
                }
                
                $Synopsis = trim($SubListDetail[0]['synopsis']);
                $SubGenre =  $SubListDetail[0]['genre'];
                for($i=0;$i<count($SubGenre);$i++){
                    $genree .=strtok($SubListDetail[0]['genre'][$i],'<').'| ';
                }
                
                $Tipe = "";
                $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
                $Years = "";
                $Score = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                $Rating = strtok($SubListDetail[0]['subDetail']['Rating'], '<');
                $Studio = "";
                $Episode=strtok($SubListDetail[0]['subDetail']['TotalEpisode'], '<');
                $Duration = "";
                $GenreList = rtrim($genree,"|");
                $imageUrl = $SubListDetail[0]['image'];

                {#Save To Mysql
                    $Slug = Str::slug($Title);
                    $code = $Slug;
                    $cdListAnime = $Slug;
                    

                    {#save To list Anime
                        $codeListAnime['code'] = md5($code);
                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                        
                        if(empty($listAnime) || $idListAnime == 0){
                            $slugListAnime = $Slug;
                            $KeyListAnimEnc= array(
                                "Title"=>trim($Title),
                                "Image"=>"",
                                "Type"=>"",
                                "href"=>$BASE_URL_LIST
                            );
                            $KeyListAnim = self::encodeKeyLiatAnime($KeyListAnimEnc);
                            if(empty($listAnime)){
                                $Input = array(
                                    'code' => md5($cdListAnime),
                                    'slug' => $slugListAnime,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $save = MainModel::insertListAnimeMysql($Input);
                            }else{
                                $conditions['id'] = $idListAnime;
                                $Update = array(
                                    'code' => md5($cdListAnime),
                                    'slug' => $slugListAnime,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                $codeListAnime['code'] = md5($cdListAnime);
                                $listAnime = MainModel::getDataListAnime($codeListAnime);
                                $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                            }
                        }
                    }#End save To list Anime

                    {#save to Detail Anime
                        $paramCheck['code'] = md5($code);
                        $checkExist = MainModel::getDataDetailAnime($paramCheck);
                        
                        $LogSave = array();
                        if(empty($checkExist)){
                            $Input = array(
                                'code' => md5($code),
                                'slug' => Str::slug($Title),
                                'title' => $Title,
                                'image' => $imageUrl,
                                'tipe' => $Tipe,
                                'genre' => $GenreList,
                                'status' => $Status,
                                'episode_total' => $Episode,
                                'years' => $Years,
                                'score' => $Score,
                                'rating' => $Rating,
                                'studio' => $Studio,
                                'duration' => $Duration,
                                'synopsis' => trim($Synopsis),
                                'id_list_anime' => $idListAnime,
                                
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            
                            $save = MainModel::insertDetailMysql($Input);
                            $LogSave = $this->saveListEpisode($SubListDetail,$idListAnime,$Title,$BASE_URL);
        
                        }else{
                            $conditions['id'] = $checkExist[0]['id'];
                            $Update = array(
                                'code' => md5($code),
                                'slug' => Str::slug($Title),
                                'title' => $Title,
                                'image' => $imageUrl,
                                'tipe' => $Tipe,
                                'genre' => $GenreList,
                                'status' => $Status,
                                'episode_total' => $Episode,
                                'years' => $Years,
                                'score' => $Score,
                                'rating' => $Rating,
                                'studio' => $Studio,
                                'duration' => $Duration,
                                'synopsis' => trim($Synopsis),
                                'id_list_anime' => $idListAnime,
                                
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            
                            $save = MainModel::updateDetailMysql($Update,$conditions);
                            $LogSave = $this->saveListEpisode($SubListDetail,$idListAnime,$Title,$BASE_URL);
                        }
                    }#End save to Detail Anime

                }#End Save To Mysql
                return $this->Success($save,$LogSave,$awal);
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }

    public static function saveListEpisode($SubListDetail,$idListAnime,$Title,$BASE_URL){
        $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
        $ListEpisode = array();
        $imageUrl = $SubListDetail[0]['image'];
        for($i=0;$i<count($SubListDetail[0]['DataEps'][0]);$i++){
            $KeyEpisodeEnc = array(
                "Title"=> $Title,
                "Image"=>$imageUrl,
                "Status" => $Status,
                "href"=>$BASE_URL."".$SubListDetail[0]['DataEps'][0][$i]['href'],
                "Episode"=>$SubListDetail[0]['DataEps'][0][$i]['nameEps'],
                
            );
            $hrefEpisode = $SubListDetail[0]['DataEps'][0][$i]['href'];
            $SlugEpisode = substr($hrefEpisode, strrpos($hrefEpisode, '/' )+1);
            $SlugEpisode = str_replace("-00","-",$SlugEpisode);
            $SlugEpisode = str_replace("-0","-",$SlugEpisode);
            $TipeMovie = (strstr($hrefEpisode,'episode')) ? "episode" : "movie";
            $Episode = $SubListDetail[0]['DataEps'][0][$i]['nameEps'];
            $code = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
            $SlugEpisode = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
            $result = base64_encode(json_encode($KeyEpisodeEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "QtYWL" . $iduniq1;
            $KeyEpisode = $result;
            
            $paramCheck['code'] = md5($code);
            $codeDetailAnime['code'] = md5(Str::slug($Title));
            $checkExist = MainModel::getDataListEpisodeAnime($paramCheck);
            $detailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
            $idDetailAnime = (empty($detailAnime)) ? 0 : $detailAnime[0]['id'];
            
            if(empty($checkExist)){
                $Input = array(
                    'code' => md5($code),
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
                    'code' => md5($code),
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

    public static function encodeKeyLiatAnime($KeyListAnimEnc){
        $result = base64_encode(json_encode($KeyListAnimEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, 500);
        $result = $iduniq0 . "QWTyu" . $iduniq1;
        $KeyListAnim = $result;
        return $KeyListAnim;
    }

    public static function SpeedResponse($awal){
        $akhir = microtime(true);
        $durasi = $akhir - $awal;
        $jam = (int)($durasi/60/60);
        $menit = (int)($durasi/60) - $jam*60;
        $detik = $durasi - $jam*60*60 - $menit*60;
        return $kecepatan = number_format((float)$detik, 2, '.', '');
    }

    public static function filterCodeDetailAnime($href){
        $hrefDetailAnime = $href;
        $SlugAnime = substr($hrefDetailAnime, strrpos($hrefDetailAnime, '/' )+1);
        $SlugAnime = str_replace("-00","-",$SlugAnime);
        $SlugAnime = str_replace("-0","-",$SlugAnime);
        $SlugAnime = str_replace("-"," ",$SlugAnime);
        return $SlugAnime;
    }
}