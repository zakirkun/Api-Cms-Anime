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

class StreamAnimeController extends Controller
{
    // keyEpisode
        public function StreamAnime(Request $request){
            $ApiKey=$request->header("X-API-KEY");
            $KeyEpisode=$request->header("KeyEpisode");
            $Token = DB::table('User')->where('token',$ApiKey)->first();
            $NextEpisode = $request->header("NextEpisode");
            $PrevEpisode = $request->header("PrevEpisode");
            if($ApiKey){
                $findCode=strstr($KeyEpisode,'QtYWL');
                $decode = str_replace('QRCAbuK', "=", $KeyEpisode);
                $iduniq0 = substr($decode, 0, 10);
                $iduniq1 = substr($decode, 10,500);
                $result = $iduniq0 . "" . $iduniq1;
                $decode2 = str_replace('QtYWL', "", $result);
                $KeyListDecode= json_decode(base64_decode($decode2));
                if($findCode){
                    $subHref=$KeyListDecode->href;
                    $BASE_URL=$ConfigController->BASE_URL_ANIME_2;
                    if($NextEpisode){
                        $URL_Next = $this->reverse_strrchr($subHref, '-');
                        $BASE_URL_LIST=$URL_Next."".$NextEpisode;
                    }elseif($PrevEpisode){
                        $URL_Prev = $this->reverse_strrchr($subHref, '-');
                        $BASE_URL_LIST=$URL_Prev."".$PrevEpisode;
                    }else{
                        $BASE_URL_LIST=$subHref;
                    }
                    
                    $ConfigController = new ConfigController();
                    $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
                    $client->getConfig('handler')->push(CloudflareMiddleware::create());
                    $goutteClient = new GoutteClient();
                    $goutteClient->setClient($client);
                    $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
                    $response = $goutteClient->getResponse();
                    $status = $response->getStatus();
                    if($status == 200){
                        // Get the latest post in this category and display the titles
                        // $SubImage= $crawler->filter('.grid3')->each(function ($node,$i) {
                        //     $ImageUrl = $node->filter('img')->attr('src');
                        //     return $ImageUrl;

                        // });
                        $SubEpisode= $crawler->filter('.epnav')->each(function ($nodel,$i) {
                                $navi= $nodel->filter('.navi > a')->each(function ($node,$i) {
                                    $Episode = $node->filter('a')->attr('href');    
                                    return $Episode;
                                }); 
                                return $navi;
                        });
                        
                        $TitleSub= $crawler->filter('.posthead')->each(function ($node,$i) {
                            $TitleSub = $node->filter('h1')->text('Default text content');
                            return $TitleSub;
                        });
                        
                        $SubSynopsis= $crawler->filter('.grid9')->each(function ($node,$i) {
                            $synopsis = $node->filter('h4.toogletarget')->text('Default text content');
                            return $synopsis;
                        });

                        
                        $ListInfoSub = $crawler->filter('.details')->each(function ($node,$i) {
                            $SubDetails = $node->filter('div')->each(function ($node,$i) {
                                $SubDetailsSpan = $node->filter('span')->each(function ($node,$i) {
                                    $details = $node->filter('span')->text('Default text content');
                                    return $details;
                                });
                                return $SubDetailsSpan;
                            });
                            return $SubDetails;
                        });
    
                        $SubMirror= $crawler->filter('.innermirrorcon')->each(function ($node,$i) {
                                $SubNameServer = $node->filter('a')->each(function ($node,$i) {
                                    $NameServer = $node->filter('a > div')->text('Default text content');
                                    $nameMirror = $node->filter('a')->attr('href');    
                                    if(empty($nameMirror)){
                                        $nameMirror = "null";
                                    }
                                    $ListMirror = [
                                        'NameMirror'=>$nameMirror,
                                        'NameServer'=>$NameServer
                                    ];
                                    return $ListMirror;
                                });                        
                                return $SubNameServer;
                        
                        });
                        if($SubMirror){
                            $ListServer = array();
                            for($i=0;$i<count($SubMirror[0]);$i++){
                                for($j=0;$j<count($SubMirror[0][$i]);$j++){
                                    $nameMirror = $SubMirror[0][$i]['NameMirror'];
                                    $serverName = $SubMirror[0][$i]['NameServer'];
                                    if($nameMirror=="null"){
                                        $nameMirror="";
                                    }
                                }
                                $crawler2 = $client->request('GET', $BASE_URL_LIST.''.$nameMirror);
                                $SubVideoEmbed= $crawler2->filter('.videoembed')->each(function (Crawler $node,$i){ 
                                    $VideoEmbed = $node->filter('iframe')->attr('src');
                                    return $VideoEmbed;
                                });
                                $ListServer[] = array(
                                    "NameServer" => $serverName,
                                    'IframeSrc' => $SubVideoEmbed[0]
                                );
                            }
                            
                            for($i=0;$i<count($ListInfoSub[0]);$i++){
                                for($j=0;$j<count($ListInfoSub[0][$i]);$j++){
                                    $Listinf=$ListInfoSub[0][$i][0];
                                    $Datainf=$ListInfoSub[0][$i][1];
                                    if($Listinf=="Tipe"){
                                        $Tipe = $Datainf;   
                                    }elseif($Listinf=="Status"){
                                        $Status = $Datainf;
                                    }elseif($Listinf=="Tahun"){
                                        $Years = $Datainf;
                                    }elseif($Listinf=="Score"){
                                        $Score = $Datainf;
                                    }elseif($Listinf=="Rating"){
                                        $Rating = $Datainf;
                                    }elseif($Listinf=="Studio"){
                                        $Studio = $Datainf;
                                    }elseif($Listinf=="Durasi"){
                                        $Duration = $Datainf;
                                    }
                                }
                            }
                            
                            // For Cek Episode
                            $LinkNowEpisode=substr($BASE_URL_LIST, strrpos($BASE_URL_LIST, '-' )+1);
                            $NowEpisode=str_replace("/","",$LinkNowEpisode);
                            $TotLink=count($SubEpisode[0])-1;
                            $ListCekEpisode=substr($SubEpisode[0][$TotLink], strrpos($SubEpisode[0][$TotLink], '-' )+1);
                            $CekEpisode=str_replace("/","",$ListCekEpisode);
                            $CekNumberEpisode = is_numeric($CekEpisode) ? $CekEpisode : 0;
                            if(count($SubEpisode[0])==3){
                                $LinkNextEpisode=substr($SubEpisode[0][2], strrpos($SubEpisode[0][2], '-' )+1);
                                $NextEpisode=str_replace("/","",$LinkNextEpisode);
                                $LinkPrevEpisode=substr($SubEpisode[0][0], strrpos($SubEpisode[0][0], '-' )+1);
                                $PrevEpisode=str_replace("/","",$LinkPrevEpisode);
                                $KeyListAnimEnc= array(
                                    "Title"=>"",
                                    "Image"=>"",
                                    "href"=>$SubEpisode[0][1]
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                            }elseif((count($SubEpisode[0])==2)&& ($NowEpisode>=$CekNumberEpisode)){
                                $NextEpisode=null;
                                $LinkPrevEpisode=substr($SubEpisode[0][0], strrpos($SubEpisode[0][0], '-' )+1);
                                $PrevEpisode=str_replace("/","",$LinkPrevEpisode);
                                $KeyListAnimEnc= array(
                                    "Title"=>"",
                                    "Image"=>"",
                                    "href"=>$SubEpisode[0][1]
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                            }elseif((count($SubEpisode[0])==2)&& ($NowEpisode<=$CekNumberEpisode)){
                                $PrevEpisode=null;
                                $LinkNextEpisode=substr($SubEpisode[0][1], strrpos($SubEpisode[0][1], '-' )+1);
                                $NextEpisode=str_replace("/","",$LinkNextEpisode);
                                $KeyListAnimEnc= array(
                                    "Title"=>"",
                                    "Image"=>"",
                                    "href"=>$SubEpisode[0][0]
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                            }else{
                                $NextEpisode=null;
                                $PrevEpisode=null;
                                $KeyListAnimEnc= array(
                                    "Title"=>"",
                                    "Image"=>"",
                                    "href"=>$SubEpisode[0][0]
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                            }
                            
                            $ListInfo = array(
                                "Tipe" => $Tipe,
                                "Status" => $Status,
                                "Episode" => $NowEpisode,
                                "Years" => $Years,
                                "Score" => $Score,
                                "Rating" => $Rating,
                                "Studio" => $Studio,
                                "Duration" => $Duration,
                                "NextEpisode"=>$NextEpisode,
                                "PrevEpisode"=>$PrevEpisode,
                                "KeyListAnim"=>$KeyListAnim
                            );
                            $ListDetail[]=array(
                                "ListInfo"=>$ListInfo,
                                "Synopsis"=>preg_replace('/(\v|\s)+/', ' ', $SubSynopsis[0])
                            );
        
                            $StreamAnime[] = array(
                                "Title"=> $TitleSub[0],
                                "Image"=>$SubImage[0],
                                "ListDetail"=>$ListDetail,
                                "ListServer"=>$ListServer
                            );
                            
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

                        }else{
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
                    }else{
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
                }else{
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
            }else{
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
            
        }

        public function reverse_strrchr($haystack, $needle)
        {
            $pos = strrpos($haystack, $needle);
            if($pos === false) {
                return $haystack;
            }
            return substr($haystack, 0, $pos + 1);
        }
}