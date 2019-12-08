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
use \Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client as Client2;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Facades\DB;

// done but masih proses debuging
class StreamAnimeController extends Controller
{
    // keyEpisode
        public function StreamAnime(Request $request){
            $ApiKey=$request->header("X-API-KEY");
            $KeyEpisode=$request->header("KeyEpisode");
            $Token = DB::table('User')->where('token',$ApiKey)->first();
            $NextEpisode = $request->header("NextEpisode");
            $PrevEpisode = $request->header("PrevEpisode");
            if($Token){
                // try{
                    $findCode=strstr($KeyEpisode,'QtYWL');
                    $KeyListDecode= $this->DecodeKeyListAnim($KeyEpisode);
                    if($findCode){
                        if($KeyListDecode){
                            $subHref=$KeyListDecode->href;
                            $ConfigController = new ConfigController();
                            $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                            if($NextEpisode){
                                $findCode=strstr($NextEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = $this->DecodePaginationEps($NextEpisode);
                                    $URL_Next=$KeyPagiDecode->href;
                                    $BASE_URL_LIST=$URL_Next;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL);
                                }else{
                                    return $this->InvalidKeyPagination();
                                }
                            }elseif($PrevEpisode){
                                $findCode=strstr($PrevEpisode,'MTrU');
                                if($findCode){
                                    $KeyPagiDecode = $this->DecodePaginationEps($PrevEpisode);
                                    $URL_PREV=$KeyPagiDecode->href;
                                    $BASE_URL_LIST=$URL_PREV;
                                    return $this->StreamValue($BASE_URL_LIST,$BASE_URL);
                                }else{
                                    return $this->InvalidKeyPagination();
                                }
                            }else{
                                $BASE_URL_LIST=$subHref;
                                return $this->StreamValue($BASE_URL_LIST,$BASE_URL);
                            }
                        }else{
                            return $this->InvalidKey();
                        }
                        
                    }else{
                        return $this->InvalidKey();
                    }
                // }catch(\Exception $e){
                //     return $this->InternalServerError();
                // }
                
            }else{
                return $this->InvalidToken();
            }
        }

        public function StreamValue($BASE_URL_LIST,$BASE_URL){
            
            $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            // Connect a 2nd user using an isolated browser and say hi!
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();

            if($status == 200){
                // for get iframe from javascript
                try{
                    $cekServer =  $crawler->filter('#change-server')->html();
                }catch(\Exception $e){
                    $cekServer ="";
                }
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
                        "Title" => substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
                        "JudulAlternatif" => substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                        "Rating" => substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                        "Votes" => substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                        "Status" => substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                        "TotalEpisode" => substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                        "HariTayang" => substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),
                    );
                    $imageUrl = $node->filter('.col-md-3')->each(function ($node,$i) {
                        $ImgUrl = $node->filter('img')->attr('src');  
                        return $ImgUrl;
                    });    
                    

                    $SubListDetail=array(
                        "subDetail" => $SubDetail02,
                        "synopsis" => $synopsis,
                        "genre" => $genre,
                        "image" => $imageUrl
                        
                    );
                    return $SubListDetail; 
                });
                $SubMirror= $crawler->filter('#change-server')->each(function ($node,$i) {
                        $SubServer = $node->filter('option')->each(function ($node,$i) {
                            $NameServer = $node->filter('option')->text('Default text content');
                            $IframeSrc = $node->filter('option')->attr('value');   
                            $ListMirror = [
                                'NameServer' => $NameServer,
                                'IframeSrc'  => $IframeSrc
                            ];
                            
                            return $ListMirror;
                        });                        
                        return $SubServer;
                });
                $PaginationEpisode = $crawler->filter('.pagination')->each(function ($node,$i) {
                    $SubPaginationEpisode = $node->filter('a')->each(function ($node,$i) {
                        $hrefPaginationEps=$node->filter('a')->attr('href');
                        $TextPeginatEps=$node->filter('a')->text('Default text content');
                        $ListPegination=array(
                            "NamePegination" => $TextPeginatEps,
                            "hrefPegination" => $hrefPaginationEps
                        );
                        return $ListPegination;
                    });
                    return $SubPaginationEpisode;
                });
                
                if($cekServer){
                    $ListServer = array();
                    for($i=1;$i<count($SubMirror[0]);$i++){
                        $ListServer[] = array(
                            "NameServer" => trim($SubMirror[0][$i]['NameServer']),
                            'IframeSrc' => ($SubMirror[0][$i]['IframeSrc']),
                        );
                    }
                    $LinkNowEpisode=substr($BASE_URL_LIST, strrpos($BASE_URL_LIST, '-' )+1);
                    $NowEpisode=str_replace("/","",$LinkNowEpisode);


                    $Title = strtok($SubListDetail[0]['subDetail']['Title'],'<');
                    $Synopsis = trim($SubListDetail[0]['synopsis']);
                    $Tipe = "";
                    $Status = strtok($SubListDetail[0]['subDetail']['Status'], '<');
                    $Years = "";
                    $Score = strtok($SubListDetail[0]['subDetail']['Votes'], '<');
                    $Rating = strtok($SubListDetail[0]['subDetail']['Rating'], '<');
                    $Studio = "";
                    $Episode=strtok($SubListDetail[0]['subDetail']['TotalEpisode'], '<');
                    $Duration = "";
                    
                    $imageUrl=$SubListDetail[0]['image'][0];
                    if(!empty($PaginationEpisode)){
                        $HrefPrev = $BASE_URL."".$PaginationEpisode[0][0]['hrefPegination'];
                        $HrefSingleList= $BASE_URL."".$PaginationEpisode[0][1]['hrefPegination'];
                        $HrefNext = $BASE_URL."".$PaginationEpisode[0][2]['hrefPegination'];
                    }else{
                        $HrefPrev = "";
                        $HrefSingleList= "";
                        $HrefNext = "";
                    }
                    $NextEpisode = $this->EncriptPaginationEps($HrefNext);
                    $KeyListAnim = $this->EncriptKeyListAnim($HrefSingleList); 
                    $PrevEpisode = $this->EncriptPaginationEps($HrefPrev); 
                    if(empty($HrefPrev)){
                        $PrevEpisode="";
                    }
                    if(empty($HrefNext)){
                        $NextEpisode="";
                    }
                    $valueEps=str_replace("=","",$NowEpisode);
                    $valueEps=str_replace("episode","",$valueEps);
                    $filterValueEps=substr($valueEps, strpos($valueEps, '&') + 1);
                    $NowEpisode=$filterValueEps;
                    $ListInfo = array(
                        "Tipe" => $Tipe,
                        "Status" => trim($Status),
                        "Episode" => $NowEpisode,
                        "Years" => $Years,
                        "Score" => $Score,
                        "Rating" => $Rating,
                        "Studio" => $Studio,
                        "Duration" => $Duration,
                        "NextEpisode"=>$NextEpisode,
                        "PrevEpisode"=>$PrevEpisode,
                        "KeyListAnim" => $KeyListAnim
                    );
                    $ListDetail[]=array(
                        "ListInfo" => $ListInfo,
                        "Synopsis" => $Synopsis
                    );

                    $StreamAnime[] = array(
                        "Title" => $Title,
                        "Image" => $imageUrl,
                        "ListDetail" => $ListDetail,
                        "ListServer" => $ListServer
                    );
                    return $this->Success($StreamAnime);
                }else{
                    return $this->PageNotFound();
                }
            }else{
                return $this->PageNotFound();
            }
        }

        public function Success($StreamAnime){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Success.",
                        "Code" => 200
                    ),
                    "Body" => array(
                        "StreamAnime" => $StreamAnime
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InvalidKeyPagination(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Key Pagination",
                        "Code" => 401
                    ),
                    "Body"=> array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InvalidKey(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Key",
                        "Code" => 401
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function PageNotFound(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Page Not Found",
                        "Code" => 404
                    ),
                    "Body" => array(
                        "StreamAnime" =>array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InternalServerError(){
            $API_TheMovie = array(
                "API_TheMovieRs" =>array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" =>"Stream Anime",
                    "Status" => "Not Complete",
                    "Message" =>array(
                        "Type" => "Info",
                        "ShortText" => "Internal Server Error",
                        "Code" => 500
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }

        public function InvalidToken(){
            $API_TheMovie = array(
                "API_TheMovieRs" => array(
                    "Version" => "N.1",
                    "Timestamp" => Carbon::now()->format(DATE_ATOM),
                    "NameEnd" => "Stream Anime",
                    "Status" => "Not Complete",
                    "Message" => array(
                        "Type" => "Info",
                        "ShortText" => "Invalid Token",
                        "Code" => 203
                    ),
                    "Body" => array(
                        "StreamAnime" => array()
                    )
                )
            );
            return $API_TheMovie;
        }

        public function FilterIframe($value){
            $valueOnclick = str_replace("changeDivContent","",$value);
            $filterValue = substr($valueOnclick, strpos($valueOnclick, '"') + 1);
            $iframe = strtok($filterValue, '"');
            return $iframe;
        }

        public function DecodePaginationEps($KeyPagination){
            $decode = str_replace('QRCAbuK', "=", $KeyPagination);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('MTrU', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            return $KeyListDecode;
        }

        public function DecodeKeyListAnim($KeyEpisode){
            $decode = str_replace('QRCAbuK', "=", $KeyEpisode);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('QtYWL', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            return $KeyListDecode;
        }

        public function EncriptPaginationEps($ListEncript){
            $KeyPegiAnimEnc= array(
                "Title"=>"",
                "Image"=>"",
                "href"=>$ListEncript
            );
            $result = base64_encode(json_encode($KeyPegiAnimEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "MTrU" . $iduniq1;
            $KeyEncript = $result;

            return $KeyEncript;
        }

        public function EncriptKeyListAnim($ListEncript){
            $KeyListAnimEnc= array(
                "Title"=>"",
                "Image"=>"",
                "href"=>$ListEncript
            );
            $result = base64_encode(json_encode($KeyListAnimEnc));
            $result = str_replace("=", "QRCAbuK", $result);
            $iduniq0 = substr($result, 0, 10);
            $iduniq1 = substr($result, 10, 500);
            $result = $iduniq0 . "QWTyu" . $iduniq1;
            $KeyEncript = $result;

            return $KeyEncript;
        }

        public function ReverseStrrchr($haystack, $needle)
        {
            $pos = strrpos($haystack, $needle);
            if($pos === false) {
                return $haystack;
            }
            return substr($haystack, 0, $pos + 1);
        }
}