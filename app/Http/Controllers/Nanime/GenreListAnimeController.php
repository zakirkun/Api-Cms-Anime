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


class GenreListAnimeController extends Controller
{
    public function GenreListAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            try{
                $ConfigController = new ConfigController();
                $BASE_URL_LIST=$ConfigController->BASE_URL_ANIME_1."/archive/genre/";
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                return $this->GenreListAnimValue($BASE_URL_LIST,$BASE_URL);
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
                "NameEnd"=>"Genre List Anime",
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

    public function Success($GenreListAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
    }

    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
    public function InvalidToken(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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

    public function GenreListAnimValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
            // Get the latest post in this category and display the titles
            $GenreListAnimeS= $crawler->filter('.single')->each(function ($node,$i) {
                
                $subGenre= $node->filter('.archiveTags')->each(function ($node,$i) {
                    $genre = $node->filter('a')->text('Default text content');
                    $href = $node->filter('a')->attr('href');
                    $subGenre = [
                        "genre"=>$genre,
                        "href"=>$href
                    ];
                    return $subGenre;
                });

                $GenreList = [
                    "subGenre"=>$subGenre
                ];
                return $GenreList;
            });
            if($GenreListAnimeS){
                for($i=0;$i<count($GenreListAnimeS[0]['subGenre']);$i++){
                    $NameIndex[]=substr($GenreListAnimeS[0]['subGenre'][$i]['genre'],0,1);
                }
                $NameIndex=array_values(array_unique($NameIndex));
                unset($NameIndex[0]);
                for($i=1;$i<=count($NameIndex);$i++){
                    
                    $ListSubIndex=array();
                    for($j=0;$j<count($GenreListAnimeS[0]['subGenre']);$j++){
                        $NameFilter=substr($GenreListAnimeS[0]['subGenre'][$j]['genre'],0,1);
                        if($NameIndex[$i]==$NameFilter){
                            $KeyListGenreEnc= array(
                                "Genre"=>$GenreListAnimeS[0]['subGenre'][$j]['genre'],
                                "href"=>$BASE_URL."".$GenreListAnimeS[0]['subGenre'][$j]['href']
                            );
                            $result = base64_encode(json_encode($KeyListGenreEnc));
                            $result = str_replace("=", "QRCAbuK", $result);
                            $iduniq0 = substr($result, 0, 10);
                            $iduniq1 = substr($result, 10, 500);
                            $result = $iduniq0 . "RqWtY" . $iduniq1;
                            $KeyListGenre = $result;
                            $ListSubIndex[] = array(
                                "Genre"=>$GenreListAnimeS[0]['subGenre'][$j]['genre'],
                                "KeyListGenre"=> $KeyListGenre
                            );
                        }
                    }
                    
                    $GenreListAnime[] = array(
                        "NameIndex"=> $NameIndex[$i],
                        "ListSubIndex"=> $ListSubIndex
                    );
                    
                }
                
                return $this->Success($GenreListAnime);
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}