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
use Illuminate\Support\Str;

#Helpers
use App\Helpers\V1\MappingResponseMysql as MappingMysql;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token
class TrandingWeekAnimeController extends Controller
{
    public function TrandingWeekAnime(Request $request){
        $ApiKey=$request->header("X-API-KEY");
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL_LIST=$BASE_URL;
                return $this->TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL);
            // }catch(\Exception $e){
            //     return $this->InternalServerError();
            // }
            
        }else{
            return $this->InvalidToken();
        }
    }
    public function InternalServerError(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Tranding Week Anime",
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

    public function Success($save,$LogSave){

        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Tranding Week Anime",
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success Save Mysql",
                    "Code" => 200
                ),
                "LogBody"=> array(
                    "DataLog"=>$LogSave
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
                "NameEnd"=>"Tranding Week Anime",
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
                "NameEnd"=>"Tranding Week Anime",
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

    public function TrandingWeekAnimValue($BASE_URL_LIST,$BASE_URL){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
            $client->getConfig('handler')->push(CloudflareMiddleware::create());
            $goutteClient = new GoutteClient();
            $goutteClient->setClient($client);
            $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
            $response = $goutteClient->getResponse();
            $status = $response->getStatus();
            if($status == 200){
                $TopListDetail= $crawler->filter('.header-image')->each(function ($node,$i) {
                    $subhref = $node->filter('.slider-item')->each(function ($nodel,$i) {
                        $href = $nodel->filter('a')->attr("href");
                        $image=$nodel->filter('.img-responsive')->attr("src");
                        $title = $nodel->filter('.img-responsive')->attr('title');
                        $ListTopnime=array(
                            "href"=>$href,
                            "image"=>$image,
                            "title"=>$title,
                            "status"=>"Ongoing"
                        );
                        
                        return $ListTopnime;
                        });  
                    return $subhref; 
                        
                });
                
                if($TopListDetail){
                    for($i=0;$i<count($TopListDetail[0]);$i++){
                        $KeyListAnimEnc=array(
                            "href"=>$BASE_URL."".$TopListDetail[0][$i]['href'],
                            "Image"=>$TopListDetail[0][$i]['image'],
                            "Title"=>$TopListDetail[0][$i]['title']
                        );
                        
                        $result = base64_encode(json_encode($KeyListAnimEnc));
                        $result = str_replace("=", "QRCAbuK", $result);
                        $iduniq0 = substr($result, 0, 10);
                        $iduniq1 = substr($result, 10, 500);
                        $result = $iduniq0 . "QWTyu" . $iduniq1;
                        $KeyListAnim = $result;
                        // $TrandingWeekAnime[] = array(
                        //     "Image"=>$TopListDetail[0][$i]['image'],
                        //     "Title"=>$TopListDetail[0][$i]['title'],
                        //     "Status"=>preg_replace('/(\v|\s)+/', ' ', $TopListDetail[0][$i]['status']),
                        //     "KeyListAnim"=>$KeyListAnim
                        // );
                        $Title = $TopListDetail[0][$i]['title'];
                        $Title = str_replace("Nonton anime:", "", $Title);
                        $Title = trim($Title);
                        $Image = $TopListDetail[0][$i]['image'];
                        $Status = preg_replace('/(\v|\s)+/', ' ', $TopListDetail[0][$i]['status']);
                        $paramCheck['code'] = md5(Str::slug($Title));
                        $codeListAnime['code'] = md5($Title);
                        $checkExist = MainModel::getDataTrendingWeek($paramCheck);
                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                        if(empty($checkExist)){
                            $Input = array(
                                "image" => $Image,
                                "title" => $Title,
                                "title_alias" => $Title,
                                "status" => $Status,
                                'slug' => Str::slug($Title),
                                'key_list_anime' => $KeyListAnim,
                                'id_list_anime' => $idListAnime,
                                'code' => md5(Str::slug($Title)),
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            $LogSave [] = "Data Save - ".$Title." ".Carbon::now()->format('Y-m-d H:i:s');
                            $save = MainModel::insertTrendingWeekMysql($Input);
                        }else{
                            $conditions['id'] = $checkExist[0]['id'];
                            $Update = array(
                                "image" => $Image,
                                "title" => $Title,
                                "title_alias" => $Title,
                                "status" => $Status,
                                'slug' => Str::slug($Title),
                                'key_list_anime' => $KeyListAnim,
                                'id_list_anime' => $idListAnime,
                                'code' => md5(Str::slug($Title)),
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            $LogSave [] =  "Data Update - ".$Title." ".Carbon::now()->format('Y-m-d H:i:s');
                            $save = MainModel::updateTrendingWeekMysql($Update,$conditions);
                        }
                        // dd($save);

                    }
                    return $this->Success($save,$LogSave);
                }else{
                    return $this->PageNotFound();
                }
            }else{
                return $this->PageNotFound();
            }
    }

}