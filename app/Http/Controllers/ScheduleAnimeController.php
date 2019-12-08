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


class ScheduleAnimeController extends Controller
{
    public function ScheduleAnime(Request $request){
        
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
            $BASE_URL_LIST=$ConfigController->BASE_URL_LIST_ANIME_2;
            $crawler = $client->request('GET', "https://animeindo.to/anime-terbaru/");
            $response = $client->getResponse();
            $status = $response->getStatus();
            if($status == 200){
                // Get the latest post in this category and display the titles
                $nodeValues = $crawler->filter('.anicalendar')->each(function (Crawler $node, $i) {
                    $List= $node->filter('.bggreylight')->each(function (Crawler $nodel, $i) {
                        $SubList= $nodel->filter('li')->each(function (Crawler $nodel, $i) {
                            $title = $nodel->filter('a')->text('Default text content');
                            $href =$nodel->filter('a')->attr('href');
                            $item = [
                                'Title'=>$title,
                                'href'=>$href
                            ];
                            return $item;
                        });
                        return $SubList;
                    });
                        $NameDay =$node->filter('.calendarday')->text('Default text content');
                        $items = [
                            'List'=>$List,
                            'NameDay'=>$NameDay
                        ];
                        return $items;

                });
                if($nodeValues){
                    $ScheduleAnime=array();
                    foreach($nodeValues as $item){
                        $NameDay=preg_replace('/(\v|\s)+/', ' ', $item['NameDay']);
                        $List=$item['List'][0];
                        $ListSubIndex = array();
                        foreach($List as $List){
                            $KeyListAnimEnc= array(
                                "Title"=>$List['Title'],
                                "Image"=>"",
                                "href"=>$List['href']
                            );
                            $result = base64_encode(json_encode($KeyListAnimEnc));
                            $result = str_replace("=", "QRCAbuK", $result);
                            $iduniq0 = substr($result, 0, 10);
                            $iduniq1 = substr($result, 10, 500);
                            $result = $iduniq0 . "QWTyu" . $iduniq1;
                            $KeyListAnim = $result;
                            $ListSubIndex[]= array(
                                "Title"=>$List['Title'],
                                "Image"=>"",
                                "KeyListAnim"=>$KeyListAnim
                            );
                            
                        }
                        $ScheduleAnime[]=array(
                            "NameDay"=>$NameDay,
                            "ListSubIndex"=>$ListSubIndex
                        );
                    }
                    $API_TheMovie=array(
                        "API_TheMovieRs"=>array(
                            "Version"=> "A.1",
                            "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                            "NameEnd"=>"Release Schedule Anime",
                            "Status"=> "Complete",
                            "Message"=>array(
                                "Type"=> "Info",
                                "ShortText"=> "Success.",
                                "Code" => 200
                            ),
                            "Body"=> array(
                                "ScheduleAnime"=>$ScheduleAnime
                            )
                        )
                    );
                    return $API_TheMovie;
                }else{
                    $API_TheMovie=array(
                        "API_TheMovieRs"=>array(
                            "Version"=> "A.1",
                            "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                            "NameEnd"=>"Release Schedule Anime",
                            "Status"=>"Not Complete",
                            "Message"=>array(  
                                "Type"=>"Info",
                                "ShortText"=>"Page Not Found.",
                                "Code"=>404
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
                        "NameEnd"=>"Release Schedule Anime",
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
                    "NameEnd"=>"Release Schedule Anime",
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