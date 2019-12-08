<?php
namespace App\Http\Controllers;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \Goutte\Client;
use \Symfony\Component\DomCrawler\Crawler;
use \GuzzleHttp\Client as GuzzleClient;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Facades\DB;


class SearchGenreAnimeController extends Controller
{
    // KeyListGenre
    public function SearchGenreAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $KeyListGenre=$request->header("KeyListGenre");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            $findCode=strstr($KeyListGenre,'RqWtY');
            $decode = str_replace('QRCAbuK', "=", $KeyListGenre);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('RqWtY', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            if($findCode){
                $subHref=substr($KeyListDecode->href, strpos($KeyListDecode->href, "genres/") + 0);
                
                $client = new Client();
                $ConfigController = new ConfigController();
                $client->setClient(new \GuzzleHttp\Client(
                    [
                        'defaults' => [
                            'timeout' => 60
                        ]
                    ]
                ));
                $GetGenre=$KeyListDecode->Genre;
                $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
                if($PageNumber<2){
                    $BASE_URL_LIST=$BASE_URL."/".$subHref;
                }else{
                    $BASE_URL_LIST=$BASE_URL."/".$subHref."/page/".$PageNumber;
                }
                $crawler = $client->request('GET', $BASE_URL_LIST);
                $response = $client->getResponse();
                $status = $response->getStatus();
                if($status == 200){
                // Get the latest post in this category and display the titles
                        $ListInfo= $crawler->filter('.grid6')->each(function (Crawler $node,$i){ 
                            $SubSatatus = $node->filter('.grid4')->each(function (Crawler $node,$i){
                                $image = $node->filter('img')->attr('src');
                                $details = [
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
                        $TotalPage= $crawler->filter('.pages')->text('Default text content');
                        $TotalSearchPage=substr($TotalPage, strpos($TotalPage, "/") + 1);
                        if(!is_numeric($TotalSearchPage)){
                            $TotalSearchPage=1;
                        }
                    if($ListInfo){
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
                                    "Status"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][0], strpos($ListInfo[$i]['SubListInfo'][0]['details'][0], ":") + 1), 
                                    "Score"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][1], strpos($ListInfo[$i]['SubListInfo'][0]['details'][0], ":") + 1),
                                    "Rating"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][2], strpos($ListInfo[$i]['SubListInfo'][0]['details'][1], ":") + 1),
                                    "Genre"=>substr($ListInfo[$i]['SubListInfo'][0]['details'][3], strpos($ListInfo[$i]['SubListInfo'][0]['details'][1], ":") + 1)
                                ),
                                "Synopsis"=>preg_replace('/(\v|\s)+/', ' ', $ListInfo[$i]['SubListInfo'][0]['synopsis'])
                            );
                            $SearchGenreAnime [] = array(
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
                                "NameEnd"=>"Search Genre Anime",
                                "Status"=>"Complete",
                                "Message"=>array(  
                                    "Type"=>"Info",
                                    "ShortText"=>"Success.",
                                    "Code"=>200
                                ),
                                "Body"=> array(
                                    "Genre"=>$GetGenre,
                                    "TotalSearchPage"=>$TotalSearchPage,
                                    "PageSearch"=>$PageNumber,
                                    "SearchGenreAnime"=>$SearchGenreAnime
                                )
                            )
                        );
                        return $API_TheMovie;
                    }else{
                        $API_TheMovie=array(
                            "API_TheMovieRs"=>array(
                                "Version"=> "A.1",
                                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                                "NameEnd"=>"Search Genre Anime",
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
                            "NameEnd"=>"Search Genre Anime",
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
                        "NameEnd"=>"Search Genre Anime",
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

            
        }else{
            $API_TheMovie=array(
                "API_TheMovieRs"=>array(
                    "Version"=> "A.1",
                    "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                    "NameEnd"=>"Search Genre Anime",
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