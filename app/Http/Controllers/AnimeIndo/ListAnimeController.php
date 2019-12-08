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
use \App\User;
use Illuminate\Support\Facades\DB;

// done
class ListAnimeController extends Controller
{

    public function ListAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $generateKey =bin2hex(random_bytes(16));
        $Token = DB::table('User')->where('token',$ApiKey)->first();
        if($Token){
            $ConfigController = new ConfigController();
            $BASE_URL_LIST=$ConfigController->BASE_URL_LIST_ANIME_2;
            return $this->ListAnimeValue($BASE_URL_LIST);
        }else{
            return $this->InvalidToken();   
        }
    }
    public function Success($ListAnime){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"List Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success.",
                    "Code" => 200
                ),
                "Body"=> array(
                    "ListAnime"=>$ListAnime
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
                "NameEnd"=>"List Anime",
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
                "Version"=> "A.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"List Anime",
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

    public function ListAnimeValue($BASE_URL_LIST){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
            // Get the latest post in this category and display the titles
            $nodeValues = $crawler->filter('.the-animelist-text')->each(function ($node,$i) {
                $List= $node->filter('.col-12')->each(function ($nodel,$i) {
                    $NameIndex =$nodel->filter('.text-h2')->text('Default text content');
                    $SubList= $nodel->filter('.list-anime')->each(function ($nodel,$i) {
                        $title = $nodel->filter('.d-flex > .title')->text('Default text content');
                        $type = $nodel->filter('.type')->text('Default text content');
                        $href =$nodel->filter('.list-anime')->attr('href');
                        $item = [
                            'Title'=>$title,
                            'href'=>$href,
                            'type'=>$type
                        ];
                        return $item;
                    });
                    $items = [
                        'List'=>$SubList,
                        'NameIndex'=>$NameIndex
                    ];
                    return $items;
                });
                return $List;
            });
            
            if($nodeValues){
                $ListAnime = array(); 
                $NameIndex= array();
                foreach($nodeValues[0] as $item){
                    $NameIndexVal = $item['NameIndex'];
                    $List=$item['List'];
                    $ListSubIndex = array();
                    foreach($List as $List){
                        $filter = substr(preg_replace('/(\v|\s)+/', ' ', $List['Title']), 0, 2);
                        $Title=$List['Title'];
                        $Type=$List['type'];
                        if($NameIndexVal=='#'){
                            if(!ctype_alpha($filter) || ctype_alpha($filter)){
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$List['href']
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                                
                                
                                $ListSubIndex[]= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            }
                        }else{
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$List['href']
                                );
                                $result = base64_encode(json_encode($KeyListAnimEnc));
                                $result = str_replace("=", "QRCAbuK", $result);
                                $iduniq0 = substr($result, 0, 10);
                                $iduniq1 = substr($result, 10, 500);
                                $result = $iduniq0 . "QWTyu" . $iduniq1;
                                $KeyListAnim = $result;
                                
                                $ListSubIndex[]= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            
                        }
                    }
                    $ListAnime[]=array(
                        "NameIndex"=>$NameIndexVal,
                        "ListSubIndex"=>$ListSubIndex
                    );
                }
                return $this->Success($ListAnime); 
            }else{
                return $this->PageNotFound(); 
            }
        }else{
            return $this->PageNotFound();   
        }
    }
    

    //
}
