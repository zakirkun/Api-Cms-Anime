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
#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class ListAnimeController extends Controller
{

    public function ListAnime(Request $request){
        $awal = microtime(true);
        $ApiKey=$request->header("X-API-KEY");
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
                "NameEnd"=>"List Anime",
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

    public function Success($Save,$LogSave,$awal){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"List Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success Save Mysql",
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
    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"List Anime",
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Page Not Found",
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
                "NameEnd"=>"List Anime",
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
                        $title = $nodel->filter('a')->text('Default text content');
                        $href =$nodel->filter('a')->attr('href');
                        $item = [
                            'Title'=>$title,
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
                    $List=$item['List'];
                    $ListSubIndex = array();
                    foreach($List as $List){
                        $filter = substr(preg_replace('/(\v|\s)+/', ' ', $List['Title']), 0, 2);
                        $Title=$List['Title'];
                        $Type=$List['type'];
                        if($NameIndexVal=='##'){
                            if(!ctype_alpha($filter) || ctype_alpha($filter)){
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                                
                                
                                $ListSubIndex[]= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            }
                        }else{
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                                
                                $ListSubIndex[]= array(
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
                
                return $this->Success($save,$LogSave,$awal);
            }else{
                return $this->PageNotFound();
            }  
        }else{
            return $this->PageNotFound();
        }
    }
    //
    public static function SpeedResponse($awal){
        $akhir = microtime(true);
        $durasi = $akhir - $awal;
        $jam = (int)($durasi/60/60);
        $menit = (int)($durasi/60) - $jam*60;
        $detik = $durasi - $jam*60*60 - $menit*60;
        return $kecepatan = number_format((float)$detik, 2, '.', '');
    }
}
