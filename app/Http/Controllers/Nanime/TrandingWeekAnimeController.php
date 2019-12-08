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

// done tinggal token
class TrandingWeekAnimeController extends Controller
{
    public function TrandingWeekAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL_LIST=$BASE_URL;
                return $this->TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL);
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
                "NameEnd"=>"Tranding Week Anime",
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

    public function Success($TrandingWeekAnime){

        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
                "Version"=> "N.1",
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
                "Version"=> "N.1",
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

    public function TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();
            if($status == 200){
                $TopListDetail= $crawler->filter('.header-image')->each(function ($node,$i) {
                    $subhref = $node->filter('.slider-item')->each(function ($nodel,$i) {
                        $href = $nodel->filter('a')->attr("href");
                        $image=$nodel->filter('.img-responsive')->attr("src");
                        $title = $nodel->filter('.img-responsive')->attr('title');
                        $ListTopnime=array(
                            "href"=>$href,
                            "image"=>$image,
                            "title"=>$title,
                            "status"=>"Ongoing"
                        );
                        
                        return $ListTopnime;
                        });  
                    return $subhref; 
                        
                });
                
                if($TopListDetail){
                    for($i=0;$i<count($TopListDetail[0]);$i++){
                        $KeyListAnimEnc=array(
                            "href"=>$BASE_URL."".$TopListDetail[0][$i]['href'],
                            "Image"=>$TopListDetail[0][$i]['image'],
                            "Title"=>$TopListDetail[0][$i]['title']
                        );
                        
                        $result = base64_encode(json_encode($KeyListAnimEnc));
                        $result = str_replace("=", "QRCAbuK", $result);
                        $iduniq0 = substr($result, 0, 10);
                        $iduniq1 = substr($result, 10, 500);
                        $result = $iduniq0 . "QWTyu" . $iduniq1;
                        $KeyListAnim = $result;
                        $TrandingWeekAnime[] = array(
                            "Image"=>$TopListDetail[0][$i]['image'],
                            "Title"=>$TopListDetail[0][$i]['title'],
                            "Status"=>preg_replace('/(\v|\s)+/', ' ', $TopListDetail[0][$i]['status']),
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