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

// done
class SingleListAnimeController extends Controller
{
    // KeyListAnim
    public function SingleListAnim(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $KeyListAnim=$request->header("KeyListAnim");
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        
        if($Token){
            try{
                $findCode=strstr($KeyListAnim,'QWTyu');
                $KeyListDecode=$this->DecodeKeylistAnime($KeyListAnim);
                if($findCode){
                    if($KeyListDecode){
                        $subHref=$KeyListDecode->href;
                        $ConfigController = new ConfigController();
                        $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                        $BASE_URL_LIST=$subHref;
                        return $this->SingleListAnimeValue($BASE_URL_LIST,$BASE_URL);
                    }else{
                        return $this->InvalidKey();
                    }                
                }else{
                    return $this->InvalidKey();
                }
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
                "NameEnd"=>"Single List Anime",
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

    public function Success($SingleListAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
                "Version"=> "N.1",
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
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Single List Anime",
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
    public function DecodeKeylistAnime($KeyListAnim){
        $decode = str_replace('QRCAbuK', "=", $KeyListAnim);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10,500);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('QWTyu', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public function SingleListAnimeValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            try{
                $DetailHref =  $crawler->filter('.col-md-12 > .episodelist')->html();
            }catch(\Exception $e){
                $DetailHref ="";
            }
            
            if($DetailHref){
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $synopsis = $node->filter('.description > p')->text('Default text content');
                    $Subgenre = $node->filter('.description')->html();
                    $imageUrl = $node->filter('.img-responsive')->attr("src");
                    $detGenre = explode("<a", $Subgenre);
                    $genre=array();
                    for($j=1;$j<count($detGenre);$j++){
                        $genre[]=substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
                    } 
                    $ListDetail = $node->filter('.animeInfo > ul')->html();
                    $SubDetail01 = explode("<b", $ListDetail);
                    $SubDetail02=array(
                        "Title"=>substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
                        "JudulAlternatif"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating"=>substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes"=>substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status"=>substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode"=>substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang"=>substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),

                    );
                    $DataEps =  $node->filter('.episodelist')->each(function ($node,$i) {
                        $SubDataEps =  $node->filter('a')->each(function ($node,$i) {
                            $hrefEps = $node->filter('a')->attr('href');
                            $NameEps = $node->filter('a')->text('Default text content');
                            $SubListDetail=array(
                                'href' => $hrefEps,
                                'nameEps'=>$NameEps,
                            );
                            return $SubListDetail; 
                        });
                        return $SubDataEps; 
                    });
                    
                    $SubListDetail=array(
                        "subDetail"=>$SubDetail02,
                        "synopsis"=>$synopsis,
                        "image"=>$imageUrl,
                        "genre"=>$genre,
                        "DataEps"=>$DataEps
                    );
                    return $SubListDetail; 
                });
            }else{
                $SubListDetail= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $synopsis = $node->filter('.description > p')->text('Default text content');
                    $Subgenre = $node->filter('.description')->html();
                    $detGenre = explode("<a", $Subgenre);
                    $genre=array();
                    for($j=1;$j<count($detGenre);$j++){
                        $genre[]=substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
                    } 
                    $ListDetail = $node->filter('.animeInfo > ul')->html();
                    $SubDetail01 = explode("<b", $ListDetail);
                    $SubDetail02=array(
                        "Title"=>substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
                        "JudulAlternatif"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating"=>substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes"=>substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status"=>substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode"=>substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang"=>substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),

                    );
                    
                    $href = $node->filter('.col-md-3 > a')->attr("href");
                    $imageUrl = $node->filter('.col-md-3 > a > img')->attr("src");
                    $DataEps[0][0]=array(
                        'href' => $href,
                        'nameEps'=>substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
                    );
                    $SubListDetail=array(
                        "subDetail"=>$SubDetail02,
                        "synopsis"=>$synopsis,
                        "image"=>$imageUrl,
                        "genre"=>$genre,
                        "DataEps"=>$DataEps
                    );
                    return $SubListDetail; 
                });
                
            }
            
            // Get the latest post in this category and display the titles
            
            if($SubListDetail){
                $genree="";
                $Title = strtok($SubListDetail[0]['subDetail']['Title'],'<');
                $Synopsis = trim($SubListDetail[0]['synopsis']);
                $SubGenre =  $SubListDetail[0]['genre'];
                for($i=0;$i<count($SubGenre);$i++){
                    $genree .=strtok($SubListDetail[0]['genre'][$i],'<').'| ';
                }
                
                
                $Tipe = "";
                $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
                $Years = "";
                $Score = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                $Rating = strtok($SubListDetail[0]['subDetail']['Rating'], '<');
                $Studio = "";
                $Episode=strtok($SubListDetail[0]['subDetail']['TotalEpisode'], '<');
                $Duration = "";
                $GenreList = rtrim($genree,"|");

                $ListInfo = array(
                    "Tipe" => $Tipe,
                    "Genre" => $GenreList,
                    "Status" => $Status,
                    "Episode" => $Episode,
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
                $imageUrl=$SubListDetail[0]['image'];
                for($i=0;$i<count($SubListDetail[0]['DataEps'][0]);$i++){
                    $KeyEpisodeEnc = array(
                        "Title"=> $Title,
                        "Image"=>$imageUrl,
                        "Status" => $Status,
                        "href"=>$BASE_URL."".$SubListDetail[0]['DataEps'][0][$i]['href'],
                        "Episode"=>$SubListDetail[0]['DataEps'][0][$i]['nameEps'],
                        
                    );
                    
                    $result = base64_encode(json_encode($KeyEpisodeEnc));
                    $result = str_replace("=", "QRCAbuK", $result);
                    $iduniq0 = substr($result, 0, 10);
                    $iduniq1 = substr($result, 10, 500);
                    $result = $iduniq0 . "QtYWL" . $iduniq1;
                    $KeyEpisode = $result;
                    $ListEpisode[] = array(
                        "Episode"=>$SubListDetail[0]['DataEps'][0][$i]['nameEps'],
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