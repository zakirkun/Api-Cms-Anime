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

// done
class SingleListAnimeController extends Controller
{
    // KeyListAnim
    public function SingleListAnim(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $KeyListAnim=$request->header("KeyListAnim");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            $findCode=strstr($KeyListAnim,'QWTyu');
            $KeyListDecode=$this->DecodeKeyListAnim($KeyListAnim);
            if($findCode){
                if($KeyListDecode){
                    $subHref=substr($KeyListDecode->href, strpos($KeyListDecode->href, "anime/") + 5);
                    $ConfigController = new ConfigController();
                    $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
                    $BASE_URL_LIST=$BASE_URL."anime".$subHref;
                    return $this->SingleListAnimValue($BASE_URL_LIST,$BASE_URL);
                }else{
                    return $this->InvalidKey();
                }
            }else{
                return $this->InvalidKey();
            }
        }else{
            return $this->InvalidToken();
        }
        
    }
    public function Success($SingleListAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
                    "Code" => 200
                ),
                "Body"=> array(
                    "SingleListAnime"=>$SingleListAnime
                )
            )
        );
        return $API_TheMovie;
    }
    public function InvalidKey(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
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
    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
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
    public function DecodeKeyListAnim($KeyListAnim){
        $decode = str_replace('QRCAbuK', "=", $KeyListAnim);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10,500);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('QWTyu', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public function InvalidToken(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
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

    public function SingleListAnimValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            // Get the latest post in this category and display the titles
            $SubListDetail = $crawler->filter('.fixed-spacing')->each(function ($node,$i) {
                $Genre= $node->filter('.animegenre')->each(function ($nodel,$i) {
                    $SubGenre= $nodel->filter('li')->each(function ($nodel,$i) {
                        $genre = $nodel->filter('a')->text('Default text content');
                        $ListGenre= [
                            'Genre'=>$genre,
                        ];
                        return $ListGenre;
                    });
                    return $SubGenre;
                });
                $Synopsis= $node->filter('.mb40')->text('Default text content');
                $Title = $node->filter('.fontsize-39')->text('Default text content');
                $listDetail = [
                    'Title' => $Title,
                    'Genre' => $Genre,
                    'Synopsis' => $Synopsis
                ];
                return $listDetail;
            });
            $image01 = $crawler->filter('.episode-ratio')->attr("style");
            $image02=substr($image01, strpos($image01, "l") + 1);
            $image03=array("(",")","'","'");
            $imageUrl=str_replace($image03, '', $image02);
            $ListInfoSub = $crawler->filter('.series-details')->each(function ($node,$i) {
                $SubDetails = $node->filter('.col-md-12')->each(function ($node,$i) {
                    $details = $node->filter('.text-h3')->text('Default text content');
                    return $details;
                });
                return $SubDetails;
            });                    
            $SubListEpisode = $crawler->filter('.episode-list')->each(function ($node,$i) {
                $TabelRow = $node->filter('.episode-list')->each(function ($node,$i) {
                    $tabelDataEp = $node->filter('.col-12')->each(function ($node,$i) {
                        $hrefEps = $node->filter('a')->attr('href');
                        $NameEps = $node->filter('.text-h4')->text('Default text content');
                        $tabelDataEps = [
                            'href' => $hrefEps,
                            'nameEps'=>$NameEps,
                        ];
                    
                        return $tabelDataEps;
                    });
                    $TabelRows = [
                        'DataEps'=>$tabelDataEp
                    ];
                    return $TabelRows;
                });
                return $TabelRow;
            });


            if($SubListDetail){
                $genree="";
                foreach($SubListDetail as $listDetail){
                    $Title = $listDetail['Title'];
                    $Synopsis = $listDetail['Synopsis'];
                    $SubGenre =  $listDetail['Genre'];
                    for($i=0;$i<count($SubGenre[0]);$i++){
                        $genree .=$SubGenre[0][$i]['Genre'].'| ';
                    }
                }
        
                $Tipe = "";
                $Status = $ListInfoSub[0][0];
                $Years = $ListInfoSub[0][3];
                $Score = "";
                $Rating = "";
                $Studio = $ListInfoSub[0][4];
                $Duration = $ListInfoSub[0][2];
                $GenreList = rtrim($genree,"|");

                $ListInfo = array(
                    "Tipe" => $Tipe,
                    "Genre" => $GenreList,
                    "Status" => $Status,
                    "Episode" => $ListInfoSub[0][1],
                    "Years" => $Years,
                    "Score" => $Score,
                    "Rating" => $Rating,
                    "Studio" => $Studio,
                    "Duration" => $Duration
                );
                $ListDetail[]=array(
                    "ListInfo"=>$ListInfo,
                    "Synopsis"=>trim($Synopsis)
                );
                
                $ListEpisode = array();
                for($i=0;$i<count($SubListEpisode[0][0]['DataEps']);$i++){
                    $KeyEpisodeEnc = array(
                        "Title"=> $Title,
                        "Image"=>$imageUrl,
                        "Status" => $Status,
                        "href"=>$SubListEpisode[0][0]['DataEps'][$i]['href'],
                        "Episode"=>$SubListEpisode[0][0]['DataEps'][$i]['nameEps'],
                        
                    );
                    
                    
                    $result = base64_encode(json_encode($KeyEpisodeEnc));
                    $result = str_replace("=", "QRCAbuK", $result);
                    $iduniq0 = substr($result, 0, 10);
                    $iduniq1 = substr($result, 10, 500);
                    $result = $iduniq0 . "QtYWL" . $iduniq1;
                    $KeyEpisode = $result;
                    $ListEpisode[] = array(
                        "Episode"=>$SubListEpisode[0][0]['DataEps'][$i]['nameEps'],
                        "DateUpload"=>"",
                        "KeyEpisode"=>$KeyEpisode
                    );
                    
                }
                $SingleListAnime[] = array(
                    "Title"=> $Title,
                    "Image"=>$imageUrl,
                    "ListDetail"=>$ListDetail,
                    "ListEpisode"=>$ListEpisode
                );

                return $this->Success($SingleListAnime);

            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}