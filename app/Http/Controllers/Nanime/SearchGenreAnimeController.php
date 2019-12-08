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


class SearchGenreAnimeController extends Controller
{
    // KeyListGenre
    public function SearchGenreAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $KeyListGenre=$request->header("KeyListGenre");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            try{
                $findCode=strstr($KeyListGenre,'RqWtY');
                $KeyListDecode=$this->DecodeKeyListGenre($KeyListGenre);
                if($findCode){
                    if($KeyListDecode){
                        $subHref=substr($KeyListDecode->href, strpos($KeyListDecode->href, "genres/") + 0);
                        $ConfigController = new ConfigController();
                        $GetGenre=$KeyListDecode->Genre;
                        $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                        if($PageNumber<2){
                            $BASE_URL_LIST=$subHref;
                        }else{
                            $BASE_URL_LIST=$subHref."?page=".$PageNumber;
                        }
                        return $this->SearchGenreAnimValue($PageNumber,$GetGenre,$BASE_URL_LIST,$BASE_URL);
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
                "NameEnd"=>"Search Genre Anime",
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
    public function Success($GetGenre,$TotalSearchPage,$PageNumber,$SearchGenreAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
    }
    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
    public function InvalidKey(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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
    public function InvalidToken(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
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

    public function FilterPageEpisode($value){
        $subHref = explode("<a", $value);
        $countHref=count($subHref);
        if($countHref>=2){
            $i=$countHref-1;
        }
        else{
            $i=$countHref;
        }
        
        $valueHref=str_replace("href","",$subHref[$i]);

        $filterValue=substr($valueHref, strpos($valueHref, '?') + 1);
        $filterValue00=substr($filterValue, strpos($filterValue, 'e') + 1);
        $filterValue01=substr($filterValue00, strpos($filterValue00, '=') + 1);
        $href = strtok($filterValue01, '"');
        return $href;
    }

    public function DecodeKeyListGenre($KeyListGenre){
        $decode = str_replace('QRCAbuK', "=", $KeyListGenre);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10,500);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('RqWtY', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public function SearchGenreAnimValue($PageNumber,$GetGenre,$BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
        // Get the latest post in this category and display the titles
                try{
                    $CekStatus=$crawler->filter('.post-title')->html();
                }catch(\Exception $e){
                    $CekStatus="";
                }
                
                $ListInfo= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                    $SubListInfo = $node->filter('.col-md-3')->each(function ($node,$i) {
                        $href = $node->filter('a')->attr('href');
                        $image = $node->filter('img')->attr("src");
                        $title = $node->filter('.post-title')->text('Default text content');
                        $status =  $node->filter('.status')->text('Default text content');
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
                
            if($CekStatus){
                $dataPage= $crawler->filter('.pagination')->html();
                $TotalSearchPage=$this->FilterPageEpisode($dataPage);
                if(!is_numeric($TotalSearchPage)){
                    $TotalSearchPage=1;
                }
                
                for($i = 0; $i<count($ListInfo[0]['SubListInfo']);$i++){
                    $ListDetail=array();
                    $Status=$ListInfo[0]['SubListInfo'][$i]['status'];
                    $Image=$ListInfo[0]['SubListInfo'][$i]['image'];
                    $Title=$ListInfo[0]['SubListInfo'][$i]['title'];
                    $Href=$BASE_URL."".$ListInfo[0]['SubListInfo'][$i]['href'];
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
                            "Score"=>"",
                            "Rating"=>"",
                            "Genre"=>""
                        ),
                        "Synopsis"=>""
                    );
                    $SearchGenreAnime [] = array(
                        "Title"=>$Title,
                        "Image"=>$Image,
                        "KeyListAnim"=>$KeyListAnim,
                        "ListDetail"=>$ListDetail
                    );
                }
                return $this->Success($GetGenre,$TotalSearchPage,$PageNumber,$SearchGenreAnime);
               
            }else{
                return $this->PageNotFound();
            }
            
        }else{
            return $this->PageNotFound();
        }
    }
}