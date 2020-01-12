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
use \App\User;
use Illuminate\Support\Str;

#Load Helper V1
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done
class ListAnimeController extends Controller
{
    /**
     * @author [Prayugo]
     * @email [example@mail.com]
     * @desc [ListAnime & ListAnimeValue Save Mysql]
     */
    // ======================= List Anime save to Mysql======================
    public function ListAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);
        if(!empty($request) || $request != NULL){
            $ApiKey = $request->header("X-API-KEY");
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        $generateKey = bin2hex(random_bytes(16));
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL_LIST=$ConfigController->BASE_URL_LIST_ANIME_1;
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                return $this->ListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("List Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("List Anime","Invalid Token", $awal);
        }
    }
    public function ListAnimeValue($BASE_URL_LIST,$BASE_URL,$awal){
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
                $List= $node->filter('.table-responsive')->each(function ($nodel,$i) {
                    $NameIndex =$nodel->filter('.col-md-12')->text('Default text content');
                    
                    $SubList= $nodel->filter('.col-md-6')->each(function ($nodel,$i) {
                        $Title = $nodel->filter('a')->text('Default text content');
                        $href =$nodel->filter('a')->attr('href');
                        $deleteEmail = ['[','email','protected',']',','];
                        if (stripos((Converter::__normalizeSummary($Title)),'[email') !== false) {
                            $Title = substr($href, strrpos($href, '/' )+1);
                            $Title = str_replace("-"," ",$Title);
                        }else{
                            $Title = $Title;
                        }
                        $item = [
                            'TitleAlias' => $Title,
                            'Title'=>$Title,
                            'href'=>$href,
                            'type'=>""
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
                    $NameIndexVal = trim($item['NameIndex']);
                    $List=$item['List'];
                    $ListSubIndex = array();
                    foreach($List as $List){
                        $filter = substr(preg_replace('/(\v|\s)+/', ' ', $List['Title']), 0, 2);
                        $Title=$List['Title'];
                        $TitleAlias=$List['TitleAlias'];
                        $Type=$List['type'];
                        if($NameIndexVal=='##'){
                            if(!ctype_alpha($filter) || ctype_alpha($filter)){
                                $KeyListAnimEnc= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                
                                $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                
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
                                    "href"=>$BASE_URL."".$List['href']
                                );
                                
                                $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                
                                $ListSubIndex[]= array(
                                    "Title"=>trim($Title),
                                    "Image"=>"",
                                    "Type"=>trim($Type),
                                    "KeyListAnim"=>$KeyListAnim
                                );
                            
                        }
                        $Slug = Str::slug($Title);
                        $code = $Slug;
                        
                        {#Save To List Anime
                            $paramCheck['code'] = md5($code);
                            $checkExist = MainModel::getDataListAnime($paramCheck);
                            if(empty($checkExist)){
                                $Input = array(
                                    'code' => md5($code),
                                    'slug' => $Slug,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => $NameIndexVal,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $LogSave [] = "Data Save - ".$Title;
                                $save = MainModel::insertListAnimeMysql($Input);
                            }else{
                                $conditions['id'] = $checkExist[0]['id'];
                                $Update = array(
                                    'code' => md5($code),
                                    'slug' => $Slug,
                                    'title' => $Title,
                                    'key_list_anime' => $KeyListAnim,
                                    'name_index' => $NameIndexVal,
                                    'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                );
                                $LogSave [] =  "Data Update - ".$Title;
                                $save = MainModel::updateListAnimeMysql($Update,$conditions);
                            }
                        }#End Save To List Anime
                    }
                    
                }
                
                return ResponseConnected::Success("List Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
            }  
        }else{
            return ResponseConnected::PageNotFound("List Anime","Page Not Found.", $awal);
        }
    }
    // ======================= End List Anime save to Mysql======================
    
    /**
     * @author [Prayugo]
     * @email [example@mail.com]
     * @desc [ListAnimeGenerate & ListAnimeValue Save Mongo]
     */
    // ======================= List Anime Generate save to Mongo ======================
    public function ListAnimeGenerate(Request $request = NULL, $params = NULL){

    }

    // ======================= End List Anime Generate save to Mongo======================
}
