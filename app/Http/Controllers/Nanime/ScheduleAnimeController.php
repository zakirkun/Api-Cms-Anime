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
use Illuminate\Support\Facades\DB;


class ScheduleAnimeController extends Controller
{
    public function ScheduleAnime(Request $request){
        
        $ApiKey=$request->header("X-API-KEY");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL_LIST=$BASE_URL."/page/jadwal-rilis/";
                return $this->ScheduleAnimeValue($BASE_URL_LIST,$BASE_URL);
            }catch(\Exception $e){
                return $this->InternalServerError();
            }
            
        }else{
            return $this->InvalidToken();
        }
        
    }
    public function InternalServerError(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Release Schedule Anime",
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
    
    public function Success($ScheduleAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Release Schedule Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
                    "Code" => 200
                ),
                "Body"=> array(
                    "ScheduleAnime"=>$ScheduleAnime
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
                "NameEnd"=>"Release Schedule Anime",
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
                "NameEnd"=>"Release Schedule Anime",
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
    public function EncriptKeyListAnim($KeyListAnimEnc){
        $result = base64_encode(json_encode($KeyListAnimEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, 500);
        $result = $iduniq0 . "QWTyu" . $iduniq1;
        $KeyEncript = $result;

        return $KeyEncript;
    }
    
    public function ScheduleAnimeValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            // Get the latest post in this category and display the titles
            $nodeValues = $crawler->filter('.panel-default')->each(function ($node,$i) {
                $List= $node->filter('.collapse')->each(function ($node,$i) {
                    $SubList= $node->filter('.col-md-3 ')->each(function ($node,$i) {
                        $title = $node->filter('.post-title')->text('Default text content');
                        $href =$node->filter('a')->attr('href');
                        $image=$node->filter('img')->attr('src');
                        $item = [
                            'Title'=>$title,
                            'href'=>$href,
                            'image'=>$image
                        ];
                        return $item;
                    });
                    
                    return $SubList;
                });
                    $NameDay =$node->filter('.panel-heading')->text('Default text content');
                    $items = [
                        'List'=>$List,
                        'NameDay'=>$NameDay
                    ];
                    return $items;
            });
            
            if($nodeValues){
                $ScheduleAnime=array();
                for($i=0;$i<7;$i++){
                    $NameDay=($nodeValues[$i]['NameDay']);
                    $ListSubIndex=array();
                    for($j=0;$j<count($nodeValues[$i]['List'][0]);$j++){
                        $KeyListAnimEnc= array(
                            "Title"=>$nodeValues[$i]['List'][0][$j]['Title'],
                            "Image"=>$nodeValues[$i]['List'][0][$j]['image'],
                            "href"=>$BASE_URL."".$nodeValues[$i]['List'][0][$j]['href'],
                        );
                        $KeyListAnim = $this->EncriptKeyListAnim($KeyListAnimEnc);
                        $ListSubIndex[]= array(
                            "Title"=>$nodeValues[$i]['List'][0][$j]['Title'],
                            "Image"=>$nodeValues[$i]['List'][0][$j]['image'],
                            "KeyListAnim"=>$KeyListAnim
                        );
                    }
                    $ScheduleAnime[]=array(
                        "NameDay"=>$NameDay,
                        "ListSubIndex"=>$ListSubIndex
                    );
                }
                return $this->Success($ScheduleAnime);
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}