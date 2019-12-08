<?php
namespace App\Http\Controllers\AnimeIndo;
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

// done tinggal token
class TrandingWeekAnimeController extends Controller
{
    public function TrandingWeekAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            $ConfigController = new ConfigController();
            $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
            $BASE_URL_LIST=$BASE_URL;
            return $this->TrandingWeekAnimeValue($BASE_URL_LIST,$BASE_URL);
        }else{
            return $this->InvalidToken();
        }
    }
    public function Success($TrandingWeekAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Tranding Week Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
                    "Code" => 200
                ),
                "Body"=> array(
                    "TrandingWeekAnime"=>$TrandingWeekAnime
                )
            )
        );
        return $API_TheMovie;
    }

    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Tranding Week Anime",
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
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Tranding Week Anime",
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

    public function TrandingWeekAnimeValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();

        if($status == 200){
            $TopListDetail= $crawler->filter('section')->each(function ($node,$i) {
                $subhref = $node->filter('.col-6')->each(function ($nodel,$i) {
                    $href = $nodel->filter('a')->attr("href");
                    $image01 = $nodel->filter('.episode-ratio')->attr("style");
                    $image02=substr($image01, strpos($image01, "l") + 1);
                    $image03=array("(",")","'","'");
                    $image=str_replace($image03, '', $image02);
                    $title = $nodel->filter('h4')->text('Default text content');
                    $status =  $nodel->filter('.status-type')->text('Default text content');
                    $ListTopnime=array(
                        "href"=>$href,
                        "image"=>$image,
                        "title"=>$title,
                        "status"=>$status
                    );
                    return $ListTopnime;
                    });  
                return $subhref; 
                    
            });
            
            if($TopListDetail){
                for($i=0;$i<count($TopListDetail[1]);$i++){
                    $KeyListAnimEnc=array(
                        "href"=>$TopListDetail[1][$i]['href'],
                        "Image"=>$TopListDetail[1][$i]['image'],
                        "Title"=>$TopListDetail[1][$i]['title']
                    );
                    $result = base64_encode(json_encode($KeyListAnimEnc));
                    $result = str_replace("=", "QRCAbuK", $result);
                    $iduniq0 = substr($result, 0, 10);
                    $iduniq1 = substr($result, 10, 500);
                    $result = $iduniq0 . "QWTyu" . $iduniq1;
                    $KeyListAnim = $result;
                    $TrandingWeekAnime[] = array(
                        "Image"=>$TopListDetail[1][$i]['image'],
                        "Title"=>$TopListDetail[1][$i]['title'],
                        "Status"=>preg_replace('/(\v|\s)+/', ' ', $TopListDetail[1][$i]['status']),
                        "KeyListAnim"=>$KeyListAnim
                    );
                }
    
                return $this->Success($TrandingWeekAnime);
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}