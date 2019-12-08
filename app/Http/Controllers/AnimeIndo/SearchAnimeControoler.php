<?php
namespace App\Http\Controllers;
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


class SearchAnimeControoler extends Controller
{
    public function SearchAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Keyword=$request->header("Keyword");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        // $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($ApiKey){
            $client = new Client();
            $ConfigController = new ConfigController();
            $client->setClient(new \GuzzleHttp\Client(
                [
                    'defaults' => [
                        'timeout' => 60
                    ]
                ]
            ));
            
            $KeywordSearch=str_replace(' ', "+", $Keyword);
            $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
            if($PageNumber<2){
                $BASE_URL_LIST=$BASE_URL."/?cat=s&s=".$KeywordSearch."&post_type=anime&submit=";
            }else{
                $BASE_URL_LIST=$BASE_URL."/page/".$PageNumber."/?cat=s&s=".$KeywordSearch."&post_type=anime&submit=";
            }
            
            
            $crawler = $client->request('GET', $BASE_URL_LIST);
            $response = $client->getResponse();
            $status = $response->getStatus();
            
            if($status == 200){
                // Get the latest post in this category and display the titles
                $ListInfo= $crawler->filter('.grid6')->each(function (Crawler $node,$i){ 
                    $SubSatatus = $node->filter('.grid4')->each(function (Crawler $node,$i){
                        $detailsStataus = $node->filter('.newepisodefloat')->text('Default text content');
                        $image = $node->filter('img')->attr('src');
                        $details = [
                            "status"=>$detailsStataus,
                            "image"=>$image
                        ];
                        return $details;
                    });
                    
                
                    $SubListInfo = $node->filter('.grid8')->each(function (Crawler $node,$i){
                        $href = $node->filter('a')->attr('href');
                        $title = $node->filter('a > h3')->text('Default text content');
                        $details = $node->filter('h4')->each(function (Crawler $node,$i){
                            $details =$node->filter('h4')->text('Default text content');
                            return $details;
                        });
                        $sinopsis =$node->filter('.serialsin')->text('Default text content');
                        $detailInfoS =[
                            "title"=>$title,
                            "href"=>$href,
                            "details"=>$details,
                            "synopsis"=>$sinopsis
                        ];
                        return $detailInfoS;
                        });
                    $ListInfoS=[
                        "SubStatus"=>$SubSatatus,
                        "SubListInfo"=>$SubListInfo
                    ];
                    return $ListInfoS;
                });
                
                if($ListInfo){
                    $TotalPage= $crawler->filter('.pages')->text('Default text content');
                    $TotPage=str_replace('.', '', $TotalPage);
                    $TotalSearchPage=substr($TotPage, strpos($TotPage, "/") + 1);
                    if(!is_numeric($TotalSearchPage)){
                        $TotalSearchPage=1;
                    }
                    
                    
                    for($i = 0; $i<count($ListInfo);$i++){
                        $ListDetail=array();
                        $KeyListAnimEnc= array(
                            "Title"=>$ListInfo[$i]['SubListInfo'][0]['title'],
                            "Image"=>$ListInfo[$i]['SubStatus'][0]['image'],
                            "href"=>$ListInfo[$i]['SubListInfo'][0]['href']
                        );
                        $result = base64_encode(json_encode($KeyListAnimEnc));
                        $result = str_replace("=", "QRCAbuK", $result);
                        $iduniq0 = substr($result, 0, 10);
                        $iduniq1 = substr($result, 10, 500);
                        $result = $iduniq0 . "QWTyu" . $iduniq1;
                        $KeyListAnim = $result;
                        
                        $ListDetail[] = array(
                            "ListInfo"=>array(
                                "Status"=>preg_replace('/(\v|\s)+/', ' ', $ListInfo[$i]['SubStatus'][0]['status']), 
                                "Years"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][0], strpos($ListInfo[$i]['SubListInfo'][0]['details'][0], ":") + 1),
                                "Rating"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][1], strpos($ListInfo[$i]['SubListInfo'][0]['details'][1], ":") + 1),
                                "Duration"=>preg_replace('/(\v|\s)+/', ' ', substr($ListInfo[$i]['SubListInfo'][0]['details'][2], strpos($ListInfo[$i]['SubListInfo'][0]['details'][2], ":") + 1))
                            ),
                             "Synopsis"=>(preg_replace('/<br>|\n/', ' ', $ListInfo[$i]['SubListInfo'][0]['synopsis']))
                        );
                        
                        $SearchAnime [] = array(
                            "Title"=>$ListInfo[$i]['SubListInfo'][0]['title'],
                            "Image"=>$ListInfo[$i]['SubStatus'][0]['image'],
                            "KeyListAnim"=>$KeyListAnim,
                            "ListDetail"=>$ListDetail
                        );
                    }
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
                }else{
                    $API_TheMovie=array(
                        "API_TheMovieRs"=>array(
                            "Version"=> "A.1",
                            "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                            "NameEnd"=>"Search Anime",
                            "Status"=>"Not Complete",
                            "Message"=>array(  
                                "Type"=>"Info",
                                "ShortText"=>"Page Not Found.",
                                "Code"=>404
                            ),
                            "Body"=> array()
                        )
                    );
                    
                    return $API_TheMovie;
                    
                }
                
                
            }else{
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
        }else{

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

        
    }
}