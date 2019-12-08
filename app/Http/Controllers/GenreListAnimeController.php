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


class GenreListAnimeController extends Controller
{
    public function GenreListAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            $client = new Client();
            $ConfigController = new ConfigController();
            $client->setClient(new \GuzzleHttp\Client(
                [
                    'defaults' => [
                        'timeout' => 60
                    ]
                ]
            ));
            $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
            $BASE_URL_LIST=$BASE_URL."/genre-anime/";
            $crawler = $client->request('GET', $BASE_URL_LIST);
            $response = $client->getResponse();
            $status = $response->getStatus();
            if($status == 200){
                // Get the latest post in this category and display the titles
                $GenreListAnimeS= $crawler->filter('.genreblock')->each(function (Crawler $node,$i){ 
                    $IndexGenre = $node->filter('h2')->text('Default text content');
                    $subGenre= $node->filter('li')->each(function (Crawler $node,$i){ 
                        $genre = $node->filter('a')->text('Default text content');
                        $href = $node->filter('a')->attr('href');
                        $subGenre = [
                            "genre"=>$genre,
                            "href"=>$href
                        ];
                        return $subGenre;
                    });
                    $GenreList = [
                        "indexGenre"=>$IndexGenre,
                        "subGenre"=>$subGenre
                    ];
                    return $GenreList;
                });
                if($GenreListAnimeS){

                    for($i=0;$i<count($GenreListAnimeS);$i++){
                        $ListSubIndex=array();
                        for($j=0;$j<count($GenreListAnimeS[$i]['subGenre']);$j++){
                            $KeyListGenreEnc= array(
                                "Genre"=>$GenreListAnimeS[$i]['subGenre'][$j]['genre'],
                                "href"=>$GenreListAnimeS[$i]['subGenre'][$j]['href']
                            );
                            $result = base64_encode(json_encode($KeyListGenreEnc));
                            $result = str_replace("=", "QRCAbuK", $result);
                            $iduniq0 = substr($result, 0, 10);
                            $iduniq1 = substr($result, 10, 500);
                            $result = $iduniq0 . "RqWtY" . $iduniq1;
                            $KeyListGenre = $result;
                            $ListSubIndex[] = array(
                                "Genre"=>$GenreListAnimeS[$i]['subGenre'][$j]['genre'],
                                "KeyListGenre"=> $KeyListGenre
                            );
                        }
                        $GenreListAnime[] = array(
                            "NameIndex"=> $GenreListAnimeS[$i]['indexGenre'],
                            "ListSubIndex"=> $ListSubIndex
                        );
                    }
                    
                    $API_TheMovie=array(
                        "API_TheMovieRs"=>array(
                            "Version"=> "A.1",
                            "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                            "NameEnd"=>"Genre List Anime",
                            "Status"=> "Complete",
                            "Message"=>array(
                                "Type"=> "Info",
                                "ShortText"=> "Success.",
                                "Code" => 200
                            ),
                            "Body"=> array(
                                "GenreListAnime"=>$GenreListAnime
                            )
                        )
                    );
                    return $API_TheMovie;
                }else{

                    $API_TheMovie=array(
                        "API_TheMovieRs"=>array(
                            "Version"=> "A.1",
                            "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                            "NameEnd"=>"Genre List Anime",
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
                
                
            }else{
                $API_TheMovie=array(
                    "API_TheMovieRs"=>array(
                        "Version"=> "A.1",
                        "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                        "NameEnd"=>"Genre List Anime",
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
        }else{

            $API_TheMovie=array(
                "API_TheMovieRs"=>array(
                    "Version"=> "A.1",
                    "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                    "NameEnd"=>"Genre List Anime",
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