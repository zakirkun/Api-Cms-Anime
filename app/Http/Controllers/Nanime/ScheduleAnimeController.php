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

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

class ScheduleAnimeController extends Controller
{
    public function ScheduleAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                $BASE_URL_LIST=$BASE_URL."/jadwal-rilis";
                return $this->ScheduleAnimeValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //      return ResponseConnected::InternalServerError("Schedule Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Schedule Anime","Invalid Token", $awal);
        }
        
    }
    
    public function ScheduleAnimeValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        if($status == 200){
            // Get the latest post in this category and display the titles
            $nodeValues = $crawler->filter('.col-md-7')->each(function ($node,$i) {
                $List = $node->filter('.box-body')->each(function ($node,$i) {
                    $SubList = $node->filter('a')->each(function ($nodel,$i) {
                        $Title = $nodel->filter('a')->text('Default text content');
                        $nameDay = $nodel->filter('h3')->text('Default text content');
                        $hrefDetail = $nodel->filter('a')->attr('href');
                        $slugDetail = substr(strrchr($hrefDetail, '/'), 1);
                        $nameIndex = substr(trim($Title), 0, 1);
                        // $nameDay = $node->filter('h3 > a')->text('Default text content');
                        $item = [
                            'TitleAlias' => $Title,
                            'href' => $hrefDetail,
                            'title' => $Title,
                            "slugDetail" => $slugDetail,
                            // "nameDay" => $nameDay,
                        ];
                        return $item;
                    });
                    
                    dd($SubList);
                    return $SubList;
                });
                dd($List);
                    $NameDay = $node->filter('h3 > a')->text('Default text content');

                    $items = [
                        'List' => $List,
                        'NameDay' => $NameDay
                    ];
                    return $items;
            });
            

            if($nodeValues){
                $ScheduleAnime=array();
                for($i=0;$i<7;$i++){
                    $NameDay = ($nodeValues[$i]['NameDay']);
                    $ListSubIndex = array();
                    for($j = 0; $j < count($nodeValues[$i]['List'][0]); $j++){
                        $href = $BASE_URL."".$nodeValues[$i]['List'][0][$j]['href'];
                        $KeyListAnimEnc = array(
                            "Title" => $nodeValues[$i]['List'][0][$j]['title'],
                            "href" => $href,
                        );
                        $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                        $Image = $nodeValues[$i]['List'][0][$j]['image'];
                        $Title = $nodeValues[$i]['List'][0][$j]['title'];
                        $Title = str_replace("(TV)", "", $Title);
                        $Title = trim($Title);
                        $TitleAlias = $nodeValues[$i]['List'][0][$j]['TitleAlias'];

                        {#Query Save to Mysql
                            $Slug = Str::slug($Title);
                            $code = $Slug;
                            $cdListAnime = $Slug;

                            {#Save to List Anime
                                $codeListAnime['code'] = md5($code);
                                $listAnime = MainModel::getDataListAnime($codeListAnime);
                                $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                if(empty($listAnime) || $idListAnime == 0){
                                    $slugListAnime = $Slug;
                                    $KeyListAnimEnc= array(
                                        "Title"=>trim($Title),
                                        "href"=>$href
                                    );
                                    $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                    if(empty($listAnime)){
                                        $Input = array(
                                            'code' => md5($cdListAnime),
                                            'slug' => $slugListAnime,
                                            'title' => $Title,
                                            'key_list_anime' => $KeyListAnim,
                                            'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        $save = MainModel::insertListAnimeMysql($Input);
                                    }else{
                                        $conditions['id'] = $idListAnime;
                                        $Update = array(
                                            'code' => md5($cdListAnime),
                                            'slug' => $slugListAnime,
                                            'title' => $Title,
                                            'key_list_anime' => $KeyListAnim,
                                            'name_index' => "#".substr(ucfirst($Title), 0, 1),
                                            'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                        );
                                        $save = MainModel::updateListAnimeMysql($Update,$conditions);
                                        $codeListAnime['code'] = md5($cdListAnime);
                                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                    }
                                }
                            }#End Save to List Anime

                            {#Save to schedule Anime
                                $paramCheck['code'] = md5($code);
                                $checkExist = MainModel::getDataScheduleAnime($paramCheck);
                                if(empty($checkExist)){
                                    $Input = array(
                                        'code' => md5($code),
                                        'slug' => $Slug,
                                        'name_day' => $NameDay,
                                        'title' => $Title,
                                        'title_alias' => $TitleAlias,
                                        'image' => $Image,
                                        'key_list_anime' => $KeyListAnim,
                                        'id_list_anime' => $idListAnime,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    
                                    $LogSave [] = "Data Save - ".$Title;
                                    $save = MainModel::insertScheduleMysql($Input);
                                }else{
                                    $conditions['id'] = $checkExist[0]['id'];
                                    $Update = array(
                                        'code' => md5($code),
                                        'slug' => $Slug,
                                        'name_day' => $NameDay,
                                        'title' => $Title,
                                        'title_alias' => $TitleAlias,
                                        'image' => $Image,
                                        'key_list_anime' => $KeyListAnim,
                                        'id_list_anime' => $idListAnime,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] =  "Data Update - ".$Title;
                                    $save = MainModel::updateScheduleWeekMysql($Update,$conditions);
                                }
                            }#End Save to schedule Anime

                        }#End Query Save to Mysql
                    }
                }
                return ResponseConnected::Success("Schedule Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("Schedule Anime","Page Not Found.", $awal);
            }
        }else{
            return ResponseConnected::PageNotFound("Schedule Anime","Page Not Found.", $awal);
        }
    }

}