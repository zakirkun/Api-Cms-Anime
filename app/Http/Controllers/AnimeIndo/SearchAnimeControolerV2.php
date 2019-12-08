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


class SearchAnimeControolerV2 extends Controller
{
    public function SearchAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Keyword=$request->header("Keyword");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            
            $ConfigController = new ConfigController();
            $KeywordSearch=str_replace(' ', "+", $Keyword);
            $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
            if($PageNumber<2){
                $BASE_URL_LIST=$BASE_URL."/?cat=s&s=".$KeywordSearch."&post_type=anime&submit=";
            }else{
                $BASE_URL_LIST=$BASE_URL."/page/".$PageNumber."/?cat=s&s=".$KeywordSearch."&post_type=anime&submit=";
            }            
            return $this->SearchAnimeValue($PageNumber,$BASE_URL_LIST,$BASE_URL);
        }else{
            return $this->InvalidToken();
        }       
    }
    public function Success($PageNumber,$TotalSearchPage,$SearchAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Search Anime",
                "Status"=>"Complete",
                "Message"=>array(  
                    "Type"=>"Info",
                    "ShortText"=>"Success.",
                    "Code"=>200
                ),
                "Body"=> array(
                    "TotalSearchPage"=>$TotalSearchPage,
                    "PageSearch"=>$PageNumber,
                    "SearchAnime"=>$SearchAnime
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
                "NameEnd"=>"Search Anime",
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
                "NameEnd"=>"Search Anime",
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

    public function SearchAnimeValue($PageNumber,$BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            // Get the latest post in this category and display the titles
            $ListInfo= $crawler->filter('.mb80')->each(function ($node,$i) {
                $SubListInfo = $node->filter('.col-6')->each(function ($node,$i) {
                    $href = $node->filter('a')->attr('href');
                    $image01 = $node->filter('.episode-ratio')->attr("style");
                    $image02=substr($image01, strpos($image01, "l") + 1);
                    $image03=array("(",")","'","'");
                    $image=str_replace($image03, '', $image02);
                    $title = $node->filter('h4')->text('Default text content');
                    $status =  $node->filter('.status-type')->text('Default text content');
                    
                    $detailInfoS =[
                        "status"=>$status,
                        "image"=>$image,
                        "title"=>$title,
                        "href"=>$href,
                        "details"=>"",
                        "synopsis"=>""
                    ];
                    return $detailInfoS;
                    });
                $ListInfoS=[
                    "SubListInfo"=>$SubListInfo
                ];
                return $ListInfoS;
            });
            
            if($ListInfo){
                $TotalPage= $crawler->filter('.page-item > .pages')->text('Default text content');
                $TotPage=str_replace('.', '', $TotalPage);
                $TotalSearchPage=substr($TotPage, strpos($TotPage, "/") + 1);
                if(!is_numeric($TotalSearchPage)){
                    $TotalSearchPage=1;
                }
                for($i = 0; $i<count($ListInfo[0]['SubListInfo']);$i++){
                    $Status=$ListInfo[0]['SubListInfo'][$i]['status'];
                    $Image=$ListInfo[0]['SubListInfo'][$i]['image'];
                    $Title=$ListInfo[0]['SubListInfo'][$i]['title'];
                    $Href=$ListInfo[0]['SubListInfo'][$i]['href'];
                    $ListDetail=array();

                    $KeyListAnimEnc= array(
                        "Title"=>$Status,
                        "Image"=>$Image,
                        "href"=>$Href
                    );
                    $result = base64_encode(json_encode($KeyListAnimEnc));
                    $result = str_replace("=", "QRCAbuK", $result);
                    $iduniq0 = substr($result, 0, 10);
                    $iduniq1 = substr($result, 10, 500);
                    $result = $iduniq0 . "QWTyu" . $iduniq1;
                    $KeyListAnim = $result;
                    
                    $ListDetail[] = array(
                        "ListInfo"=>array(
                            "Status"=>trim($Status), 
                            "Years"=>"",
                            "Rating"=>"",
                            "Duration"=>""
                        ),
                         "Synopsis"=>""
                    );
                    
                    $SearchAnime [] = array(
                        "Title"=>$Title,
                        "Image"=>$Image,
                        "KeyListAnim"=>$KeyListAnim,
                        "ListDetail"=>$ListDetail
                    );
                }
                return $this->Success($PageNumber,$TotalSearchPage,$SearchAnime);
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}