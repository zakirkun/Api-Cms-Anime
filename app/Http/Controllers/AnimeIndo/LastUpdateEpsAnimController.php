<?php
namespace App\Http\Controllers\AnimeIndo;
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
use Illuminate\Support\Facades\DB;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    public function LastUpdateAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        
        if($Token){
            $ConfigController = new ConfigController();
            $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
            if($PageNumber<2){
                $BASE_URL_LIST=$BASE_URL;
            }else{
                $BASE_URL_LIST=$BASE_URL."/page/".$PageNumber;
            }
            return $this->LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL);
        }else{
            return $this->InvalidToken();
        }
    }
    public function Success($TotalSearchPage,$PageNumber,$LastUpdateAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Last Update Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
                    "Code" => 200
                ),
                "Body"=> array(
                    "TotalSearchPage"=>$TotalSearchPage,
                    "PageSearch"=>$PageNumber,
                    "LastUpdateAnime"=>$LastUpdateAnime
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
                "NameEnd"=>"Last Update Anime",
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
                "NameEnd"=>"Last Update Anime",
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

    public function LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        // $Body=(string)$response->getBody();
        
        if($status == 200){
            $LastUpdateEps= $crawler->filter('.item-lists')->each(function ($node,$i) {
                $subhref = $node->filter('.col-6')->each(function ($nodel, $i) {
                    $href = $nodel->filter('a')->attr("href");
                    $image01 = $nodel->filter('.episode-ratio')->attr("style");
                    $image02=substr($image01, strpos($image01, "l") + 1);
                    $image03=array("(",")","'","'");
                    $image=str_replace($image03, '', $image02);
                    $title = $nodel->filter('h4')->text('Default text content');
                    $status =  $nodel->filter('.status-type')->text('Default text content');
                    $episode =  $nodel->filter('.episode-number')->text('Default text content');
                    $ListUpdtnime=array(
                            "href"=>$href,
                            "image"=>$image,
                            "title"=>$title,
                            "status"=>$status,
                            "episode"=>$episode
                    );
                    
                    return $ListUpdtnime;
                });  
                return $subhref; 
            });
            
            if($LastUpdateEps){
                $TotalPage= $crawler->filter('.page-item > .pages')->text('Default text content');
                $TotPage=str_replace('.', '', $TotalPage);
                $TotalSearchPage=substr($TotPage, strpos($TotPage, "/") + 1);
                if(!is_numeric($TotalSearchPage)){
                    $TotalSearchPage=1;
                }
                if($PageNumber<=$TotalSearchPage){
                    for($i=0;$i<count($LastUpdateEps[0]);$i++){
                        $KeyEpisodeEnc=array(
                            "href"=>$LastUpdateEps[0][$i]['href'],
                            "Image"=>$LastUpdateEps[0][$i]['image'],
                            "Title"=>$LastUpdateEps[0][$i]['title'],
                            "Status"=>$LastUpdateEps[0][$i]['status'],
                            "Episode"=>$LastUpdateEps[0][$i]['episode']
                        );
                        $result = base64_encode(json_encode($KeyEpisodeEnc));
                        $result = str_replace("=", "QRCAbuK", $result);
                        $iduniq0 = substr($result, 0, 10);
                        $iduniq1 = substr($result, 10, 500);
                        $result = $iduniq0 . "QtYWL" . $iduniq1;
                        $KeyEpisode = $result;
                        $LastUpdateAnime[] = array(
                            "Image"=>$LastUpdateEps[0][$i]['image'],
                            "Title"=>$LastUpdateEps[0][$i]['title'],
                            "Status"=>preg_replace('/(\v|\s)+/', ' ', $LastUpdateEps[0][$i]['status']),
                            "Episode"=>$LastUpdateEps[0][$i]['episode'],
                            "KeyEpisode"=>$KeyEpisode
                        );
                    }
                    
                    return $this->Success($TotalSearchPage,$PageNumber,$LastUpdateAnime);
                }else{
                    return $this->PageNotFound();
                }
                
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}