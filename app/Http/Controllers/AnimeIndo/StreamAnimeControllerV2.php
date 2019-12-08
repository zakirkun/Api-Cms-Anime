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
use \Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\Client as Client2;
use \Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Support\Facades\DB;

// done but masih proses debuging
class StreamAnimeControllerV2 extends Controller
{
    // keyEpisode
        public function StreamAnime(Request $request){
            $ApiKey=$request->header("X-API-KEY");
            $KeyEpisode=$request->header("KeyEpisode");
            $Token = DB::table('User')->where('token',$ApiKey)->first();
            $NextEpisode = $request->header("NextEpisode");
            $PrevEpisode = $request->header("PrevEpisode");
            if($Token){
                $findCode=strstr($KeyEpisode,'QtYWL');
                $KeyListDecode=$this->DecodeKeyEpisode($KeyEpisode);
                if($findCode){
                    if($KeyListDecode){
                        $subHref=$KeyListDecode->href;
                        $ConfigController = new ConfigController();
                        $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
                        if($NextEpisode){
                            $URL_Next = $this->ReverseStrrchr($subHref, '-');
                            $BASE_URL_LIST=$URL_Next."".$NextEpisode;
                        }elseif($PrevEpisode){
                            $URL_Prev = $this->ReverseStrrchr($subHref, '-');
                            $BASE_URL_LIST=$URL_Prev."".$PrevEpisode;
                        }else{
                            $BASE_URL_LIST=$subHref;
                        }
                        return $this->StreamAnimeValue($KeyListDecode,$BASE_URL_LIST,$BASE_URL);
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
        
        public function DecodeKeyEpisode($KeyEpisode){
            $decode = str_replace('QRCAbuK', "=", $KeyEpisode);
            $iduniq0 = substr($decode, 0, 10);
            $iduniq1 = substr($decode, 10,500);
            $result = $iduniq0 . "" . $iduniq1;
            $decode2 = str_replace('QtYWL', "", $result);
            $KeyListDecode= json_decode(base64_decode($decode2));
            return $KeyListDecode;
        }

        public function FilterIframe($value){
            $valueOnclick=str_replace("changeDivContent","",$value);
            $filterValue=substr($valueOnclick, strpos($valueOnclick, '"') + 1);
            $iframe = strtok($filterValue, '"');
            return $iframe;
        }

        public function ReverseStrrchr($haystack, $needle)
        {
            $pos = strrpos($haystack, $needle);
            if($pos === false) {
                return $haystack;
            }
            return substr($haystack, 0, $pos + 1);
        }

        public function Success($StreamAnime){
            $API_TheMovie=array(
                "API_TheMovieRs"=>array(
                    "Version"=> "A.1",
                    "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                    "NameEnd"=>"Stream Anime",
                    "Status"=> "Complete",
                    "Message"=>array(
                        "Type"=> "Info",
                        "ShortText"=> "Success.",
                        "Code" => 200
                    ),
                    "Body"=> array(
                        "StreamAnime"=>$StreamAnime
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
                    "NameEnd"=>"Stream Anime",
                    "Status"=> "Not Complete",
                    "Message"=>array(
                        "Type"=> "Info",
                        "ShortText"=> "Invalid Key",
                        "Code" => 401
                    ),
                    "Body"=> array(
                        "StreamAnime"=>array()
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
                    "NameEnd"=>"Stream Anime",
                    "Status"=> "Not Complete",
                    "Message"=>array(
                        "Type"=> "Info",
                        "ShortText"=> "Page Not Found",
                        "Code" => 404
                    ),
                    "Body"=> array(
                        "StreamAnime"=>array()
                    )
                )
            );
            return $API_TheMovie;
        }
        public function InvalidToken(){
            $API_TheMovie=array(
                "API_TheMovieRs"=>array(
                    "Version"=> "A.1",
                    "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                    "NameEnd"=>"Stream Anime",
                    "Status"=> "Not Complete",
                    "Message"=>array(
                        "Type"=> "Info",
                        "ShortText"=> "Invalid Token",
                        "Code" => 203
                    ),
                    "Body"=> array(
                        "StreamAnime"=>array()
                    )
                )
            );
            return $API_TheMovie;
        }

        public function StreamAnimeValue($KeyListDecode,$BASE_URL_LIST,$BASE_URL){
            $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
        
            // Connect a 2nd user using an isolated browser and say hi!
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $crawle2 = $client->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $body=$crawle2->getBody()->getContents();
            $dom = new \DOMDocument();
            @$dom->loadHtml($body);
            $xpath = new \DOMXPath($dom);
            $status = $response->getStatus();
            if($status == 200){
                // for get iframe from javascript
                $onclicks = $xpath->query("//button/@onclick");
                $dataIframe=array();    
                foreach ($onclicks as $onclick) {
                    $iframe = $this->FilterIframe($onclick->nodeValue);
                    $dataIframe[]=array(
                        "iframe"=>json_encode($iframe)
                    );
                }
                $TitleSub = $crawler->filter('.text-white')->text('Default text content');
                $SubMirror= $crawler->filter('.mirror-buttons')->each(function ($node,$i) {
                        $SubServer = $node->filter('.button-col')->each(function ($node,$i) {
                            $NameServer = $node->filter('div')->text('Default text content');
                            $ListMirror = [
                                'NameServer'=>$NameServer
                            ];
                            return $ListMirror;
                        });                        
                        return $SubServer;
                });
                
                if($SubMirror){
                    $ListServer = array();
                    for($i=0;$i<count($dataIframe);$i++){
                        $ListServer[] = array(
                            "NameServer" => trim($SubMirror[0][$i]['NameServer']),
                            'IframeSrc' => $dataIframe[$i]['iframe']
                        );
                    }
                    
                    $LinkNowEpisode=substr($BASE_URL_LIST, strrpos($BASE_URL_LIST, '-' )+1);
                    $NowEpisode=str_replace("/","",$LinkNowEpisode);
                    $Tipe = "";   
                    $Status = $KeyListDecode->Status;
                    $Years = "";
                    $Score = "";
                    $Rating = "";
                    $Studio = "";
                    $Duration = "";
                    $Image= $KeyListDecode->Image;
                    
                    $ListInfo = array(
                        "Tipe" => $Tipe,
                        "Status" => trim($Status),
                        "Episode" => $NowEpisode,
                        "Years" => $Years,
                        "Score" => $Score,
                        "Rating" => $Rating,
                        "Studio" => $Studio,
                        "Duration" => $Duration,
                        "NextEpisode"=>"",
                        "PrevEpisode"=>"",
                        "KeyListAnim"=>""
                    );
                    $ListDetail[]=array(
                        "ListInfo"=>$ListInfo,
                        "Synopsis"=>""
                    );

                    $StreamAnime[] = array(
                        "Title"=> $TitleSub,
                        "Image"=>$Image,
                        "ListDetail"=>$ListDetail,
                        "ListServer"=>$ListServer
                    );
                    
                    return $this->Success($StreamAnime);

                }else{
                    return $this->PageNotFound();
                }
            }else{
                return $this->PageNotFound();
            }
        }
}