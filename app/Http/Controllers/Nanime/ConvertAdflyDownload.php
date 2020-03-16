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

use Config;

#Load Helper V1
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;
use App\Helpers\V1\Adfly\libAdfly;

#Load Models V1
use App\Models\V1\MainModel as MainModel;
use App\Models\V1\Mongo\MainModelMongo as MainModelMongo;

// done
class ConvertAdflyDownload extends Controller
{
    public function __construct(){
        ini_set("memory_limit","2048M");
    }

    public function AdflyDownload(Request $request = NULL, $param = null){
        $awal = microtime(true); 
        $userId = '22953517';
        $publicApiKey = 'b8cf4c4cd9588e01ee68d70912bd49d8';
        $scretApikey = 'e69a1f71-8700-4a0d-ad52-b6c71bdad903';

        // dd(ResponseConnected::SpeedResponse($awal));
        $params = $param; # get param dari populartopiclist atau dari cron
        if(is_null($param)) $params = $request->all();

        $adfly = new libAdfly($userId, $publicApiKey, $scretApikey);
        if(!empty($request) || $request != NULL){
            $ApiKey = !empty($request->header("X-API-KEY")) ? $request->header("X-API-KEY") : '' ;
        }
        if(empty($ApiKey)){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        
        $Users = MainModel::getUser($ApiKey);
        if(empty($Users)){
            return ResponseConnected::InvalidToken("Adfly Anime","Invalid Token", $awal);
        }
        
        $id = (isset($params['params']['id']) ? $params['params']['id'] : NULL);
        $idStreamAnime = (isset($params['params']['id_stream_anime']) ? $params['params']['id_stream_anime'] : '');
        $code = (isset($params['params']['code']) ? $params['params']['code'] : '');
        $startDate = (isset($params['params']['start_date']) ? $params['params']['start_date'] : NULL);
        $endDate = (isset($params['params']['end_date']) ? $params['params']['end_date'] : NULL);
        $isUpdated = (isset($params['params']['is_updated']) ? filter_var($params['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        $showLog = (isset($params['params']['show_log']) ? $params['params']['show_log'] : FALSE);
        $parameter = [
            'id' => $id,
            'id_stream_anime' => $idStreamAnime,
            'code' => $code,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_updated' => $isUpdated
        ];
        $dataDownload = MainModel::getDownloadStream($parameter);
        $DataLinkDownload = array();
        $DataAll = array();
        $i = 0;
        $LogSave = array();
        $save  = '';
        $get = $adfly->getGroups(1);
        if(count($dataDownload) > 0){
            foreach($dataDownload as $linkDownlaod){
                // $DataLinkDownload[] = $linkDownlaod['link_download'];
                $Adfly = $adfly->shorten(array($linkDownlaod['link_download']), 0,0,'741923','Nime IndoV1 - '.$linkDownlaod['id']);
                $dataAdfly = json_decode($Adfly,TRUE);
                
                if(count($dataAdfly['warnings']) > 0){
                    $message = $dataAdfly['warnings'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Convert Adfly Download Anime", $message ,$awal);
                }elseif(count($dataAdfly['errors']) > 0){
                    $message = $dataAdfly['errors'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Convert Adfly Download Anime", $message ,$awal);
                }else{
                    $adfly->updateUrl($dataAdfly['data'][0]['id'],741923);
                    $conditions['id'] = $linkDownlaod['id'];
                    $Update = array(
                        'id_stream_anime' => $linkDownlaod['id_stream_anime'],
                        'code' => $linkDownlaod['code'],
                        'name_server' => $linkDownlaod['name_server'],
                        'link_download' => $linkDownlaod['link_download'],
                        'adfly_link_download' => $dataAdfly['data'][0]['short_url'],
                        "id_adfly" => $dataAdfly['data'][0]['id'],
                        "adfly_group" => 'AnimeV1',
                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                    );
                
                    $LogSave = [
                        'id' => $linkDownlaod['id'],
                        'Data_Download' => $Update,
                    ];
                    $save = MainModel::updateDownloadStreamMysql($Update,$conditions);
                }
                
            }
            
            return ResponseConnected::Success("Convert Adfly Download Anime", $save, $LogSave, $awal);
        }
        
        return ResponseConnected::PageNotFound("Convert Adfly Download Anime","Data Not Found. Id = ".$id, $awal);
    }

    public function DeleteAdflyDownload(Request $request = NULL, $param = null){
        $userId = '22953517';
        $publicApiKey = 'b8cf4c4cd9588e01ee68d70912bd49d8';
        $scretApikey = 'e69a1f71-8700-4a0d-ad52-b6c71bdad903';

        $awal = microtime(true);        
        $params = $param; # get param dari populartopiclist atau dari cron
        if(is_null($param)) $params = $request->all();

        $adfly = new libAdfly($userId, $publicApiKey, $scretApikey);
        if(!empty($request) || $request != NULL){
            $ApiKey = !empty($request->header("X-API-KEY")) ? $request->header("X-API-KEY") : '' ;
        }
        if(empty($ApiKey)){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        
        $Users = MainModel::getUser($ApiKey);
        if(empty($Users)){
            return ResponseConnected::InvalidToken("Adfly Anime","Invalid Token", $awal);
        }
        
        $id = (isset($params['params']['id']) ? $params['params']['id'] : NULL);
        $idStreamAnime = (isset($params['params']['id_stream_anime']) ? $params['params']['id_stream_anime'] : '');
        $code = (isset($params['params']['code']) ? $params['params']['code'] : '');
        $startDate = (isset($params['params']['start_date']) ? $params['params']['start_date'] : NULL);
        $endDate = (isset($params['params']['end_date']) ? $params['params']['end_date'] : NULL);
        $isUpdated = (isset($params['params']['is_updated']) ? filter_var($params['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);
        $showLog = (isset($params['params']['show_log']) ? $params['params']['show_log'] : FALSE);
        $parameter = [
            'id' => $id,
            'id_stream_anime' => $idStreamAnime,
            'code' => $code,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_updated' => $isUpdated
        ];
        $dataDownload = MainModel::getDownloadStream($parameter);
        $DataLinkDownload = array();
        $DataAll = array();
        $i = 0;
        $LogSave = array();
        $save  = '';
        $get = $adfly->getGroups(1);
        if(count($dataDownload) > 0){
            foreach($dataDownload as $linkDownlaod){
                $Adfly = $adfly->deleteUrl($linkDownlaod['id_adfly']);
                $dataAdfly = json_decode($Adfly,TRUE);
                
                if(count($dataAdfly['warnings']) > 0){
                    $message = $dataAdfly['warnings'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Convert Adfly Download Anime", $message ,$awal);
                }elseif(count($dataAdfly['errors']) > 0){
                    $message = $dataAdfly['errors'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Convert Adfly Download Anime", $message ,$awal);
                }else{
                    // $adfly->updateUrl($dataAdfly['data'][0]['id'],741923);
                    $conditions['id'] = $linkDownlaod['id'];
                    $Update = array(
                        'id_stream_anime' => $linkDownlaod['id_stream_anime'],
                        'code' => $linkDownlaod['code'],
                        'name_server' => $linkDownlaod['name_server'],
                        'link_download' => $linkDownlaod['link_download'],
                        'adfly_link_download' => '',
                        "id_adfly" => '',
                        "adfly_group" => '',
                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                    );
                
                    $LogSave = [
                        'id' => $linkDownlaod['id'],
                        'Data_Download' => $Update,
                    ];
                    $save = MainModel::updateDownloadStreamMysql($Update,$conditions);
                }
            }
            return ResponseConnected::Success("Delete Convert Adfly Download Anime", $save, $LogSave, $awal);
        }
        
        return ResponseConnected::PageNotFound("Delete Convert Adfly Download Anime","Data Not Found. Id = ".$id, $awal);
    }

    public function DeleteAdflyDownloadbyGroup(Request $request = NULL, $param = null){
        $awal = microtime(true); 
        $userId = '22953517';
        $publicApiKey = 'b8cf4c4cd9588e01ee68d70912bd49d8';
        $scretApikey = 'e69a1f71-8700-4a0d-ad52-b6c71bdad903';
       
        $params = $param; # get param dari populartopiclist atau dari cron
        if(is_null($param)) $params = $request->all();

        $adfly = new libAdfly($userId, $publicApiKey, $scretApikey);
        if(!empty($request) || $request != NULL){
            $ApiKey = !empty($request->header("X-API-KEY")) ? $request->header("X-API-KEY") : '' ;
        }
        if(empty($ApiKey)){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
        }
        
        $Users = MainModel::getUser($ApiKey);
        if(empty($Users)){
            return ResponseConnected::InvalidToken("Adfly Anime","Invalid Token", $awal);
        }

        $startPage = (isset($params['params']['start_page']) ? $params['params']['start_page'] : NULL);
        $total = 0;
        $hit = 0;
        $getUrl = $adfly->getUrls($startPage);
        $dataGroupUrl = json_decode($getUrl,TRUE);

        if(count($dataGroupUrl['warnings']) > 0){
            $message = $dataGroupUrl['warnings'];
            dd($message);
            return ResponseConnected::InternalServerError("Delete Convert Adfly Download Anime by Group", $message ,$awal);
        }elseif(count($dataGroupUrl['errors']) > 0){
            $message = $dataGroupUrl['errors'];
            dd($message);
            return ResponseConnected::InternalServerError("Delete Convert Adfly Download Anime by Group", $message ,$awal);
        }else{
            $LogSave = array();
            foreach($dataGroupUrl['data'] as $dataGrpUrl){
                $idAdfly = $dataGrpUrl['id'];
                $Adfly = $adfly->deleteUrl($idAdfly);
                $dataAdfly = json_decode($Adfly,TRUE);
                if(count($dataAdfly['warnings']) > 0){
                    $message = $dataAdfly['warnings'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Delete Convert Adfly Download Anime by Group", $message ,$awal);
                }elseif(count($dataAdfly['errors']) > 0){
                    $message = $dataAdfly['errors'];
                    dd($message);
                    return ResponseConnected::InternalServerError("Delete Convert Adfly Download Anime by Group", $message ,$awal);
                }else{
                    $DataSave[] = [
                        'Title' => $dataGrpUrl['id'],
                        'Id' => $dataGrpUrl['id'],
                        'PageNumber' => $startPage
                    ];
                    $total = $hit++;
                }
            }   
            $LogSave[] = [
                'TotalData' => $total,
                'Data' => $DataSave
            ];
            return ResponseConnected::Success("Delete Convert Adfly Download Anime by Group", 'Delete Adfly By Group', $LogSave, $awal);
        }
        
    }


        // $get = $adfly->deleteUrl(6475366061);
        // $data = json_decode($get, TRUE);
        // $daa  = $data['data'];
        // $cek = $data[0]['data'];
        // $get = $adfly->getUrls(1);
        // print_r($get);exit;
        
        // $get = $adfly->getGroups(1);
        // $get = $adfly->createGroup('An');
        // $get = $adfly->deleteGroup('sus');
        // $get = $adfly->getAccountDetails();
}