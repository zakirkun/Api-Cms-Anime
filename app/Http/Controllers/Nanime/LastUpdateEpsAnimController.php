<?php
namespace App\Http\Controllers\Nanime;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \App\Http\Controllers\ConfigController;
use \Symfony\Component\DomCrawler\Crawler;
use \GuzzleHttp\Client;
use \Goutte\Client as GoutteClient; 
use \Tuna\CloudflareMiddleware;
use \GuzzleHttp\Cookie\FileCookieJar;
use \GuzzleHttp\Psr7;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;
use \Jenssegers\Agent\Agent;
use Illuminate\Support\Str;

#Helpers
use App\Helpers\V1\MappingResponseMysql as MappingMysql;
use App\Helpers\V1\Converter as Converter;
use App\Helpers\V1\ResponseConnected as ResponseConnected;
use App\Helpers\V1\EnkripsiData as EnkripsiData;

#Load Controller
use App\Http\Controllers\Nanime\DetailListAnimeController;

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [__construct]
     */
    // ================  __construct =========================================
    public function __construct()
    {
        $this->DetailListAnimeController = new DetailListAnimeController();
    }
    // ================ End __construct =========================================

    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [LastUpdateAnime]
     */
    // ================  LastUpdateAnime Save To Mysql =========================================
    public function LastUpdateAnime(Request $request = NULL, $params = NULL){
        $awal = microtime(true);        
        if(!empty($request) || $request != NULL){
            $ApiKey=$request->header("X-API-KEY");
            $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        }
        if(!empty($params) || $params != NULL){
            $ApiKey = (isset($params['params']['X-API-KEY']) ? ($params['params']['X-API-KEY']) : '');
            $PageNumber = (isset($params['params']['PageNumber'])? ($params['params']['PageNumber']) : 1);
        }
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            // try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                if($PageNumber<2){
                    $BASE_URL_LIST=$BASE_URL;
                }else{
                    $BASE_URL_LIST=$BASE_URL."/?page=".$PageNumber;
                }
                return $this->LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Last Update Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Last Update Anime","Page Not Found.", $awal);
        }
    }

    public function FilterHreftEpisode($value){
        $subHref = explode("<a", $value);
        $valueHref=str_replace("href","",$subHref[1]);
        $filterValue=substr($valueHref, strpos($valueHref, '"') + 1);
        $href = strtok($filterValue, '"');
        return $href;
    }
    public function FilterPageEpisode($value){
        $subHref = explode("<a", $value);
        $countHref=count($subHref);
        if($countHref>=8){
            $i=$countHref-1;
        }else{
            $i=$countHref;
        }
        $valueHref=str_replace("href","",$subHref[$i]);
        $filterValue=substr($valueHref, strpos($valueHref, '?') + 1);
        $filterValue01=substr($filterValue, strpos($filterValue, '=') + 1);
        $href = strtok($filterValue01, '"');
        return $href;
    }

    public function LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        
        // $Body=(string)$response->getBody();
        if($status == 200){
            $LastUpdateEps= $crawler->filter('.col-md-7')->each(function ($node,$i) {
                
                $subhref = $node->filter('.col-md-3')->each(function ($nodel, $i) {
                    $href = $nodel->filter('a')->attr("href");
                    $image = $nodel->filter('img')->attr("src");
                    $titleAlias = $nodel->filter('.post-title')->text('Default text content');
                    $title = $nodel->filter('.post-title')->attr("title");
                    $status =  $nodel->filter('.status')->text('Default text content');
                    $episode =  $nodel->filter('.episode')->text('Default text content');
                    
                    $ListUpdtnime = array(
                            "hrefSingleList" => $href,
                            "image" => $image,
                            "titleAlias" => $titleAlias,
                            "title" => $title,
                            "status" => $status,
                            "episode" => $episode
                    );
                    
                    return $ListUpdtnime;
                });
                return $subhref; 
            });
            
            if($LastUpdateEps){
                
                $SingleEpisode = array();
                $hrefKeyListAnim = array();
                for($i=0;$i<count($LastUpdateEps[0]);$i++){
                    $SingleListHref=$BASE_URL."".$LastUpdateEps[0][$i]['hrefSingleList'];
                    $hrefKeyListAnim[] = $SingleListHref;
                    $crawler2 = $goutteClient->request('GET', $SingleListHref);
                    $response2 = $goutteClient->getResponse();
                    try{
                        $DetailHref =  $crawler2->filter('.col-md-12 > .episodelist')->html();
                    }catch(\Exception $e){
                        $DetailHref ="";
                    }
                    
                    if($DetailHref){
                        $SubListDetail= $crawler2->filter('.col-md-7')->each(function ($node,$i) {
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
                                "Judul"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                                "JudulAlternatif"=>substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                                "Rating"=>substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                                "Votes"=>substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                                "Status"=>substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                                "TotalEpisode"=>substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                                "HariTayang"=>substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),

                            );
                            $DetailHref =  $node->filter('.col-md-12 > .episodelist')->html();
                            $href = $this->FilterHreftEpisode($DetailHref);
                            $SubListDetail=array(
                                "subDetail"=>$SubDetail02,
                                "synopsis"=>$synopsis,
                                "genre"=>$genre,
                                "hrefEpisode"=>$href
                            );
                            return $SubListDetail; 
                        });
                    }else{
                        $SubListDetail= $crawler2->filter('.col-md-7')->each(function ($node,$i) {

                            $synopsis = $node->filter('.description > p')->text('Default text content');
                            $Subgenre = $node->filter('.description')->html();
                            $detGenre = explode("<a", $Subgenre);
                            $genre = array();
                            for($j = 1; $j<count($detGenre); $j++){
                                $genre[] = trim(str_replace("</a>","",substr($detGenre[$j], strpos($detGenre[$j], ">") + 1)));
                            } 
                            $ListDetail = $node->filter('.animeInfo > ul')->html();
                            $SubDetail01 = explode("<b", $ListDetail);
                            $SubDetail02 = array(
                                "Judul" => substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
                                "JudulAlternatif" => substr($SubDetail01[2], strpos($SubDetail01[2], ":") + 1),
                                "Rating" => substr($SubDetail01[3], strpos($SubDetail01[3], ":") + 1),
                                "Votes" => substr($SubDetail01[4], strpos($SubDetail01[4], ":") + 1),
                                "Status" => substr($SubDetail01[5], strpos($SubDetail01[5], ":") + 1),
                                "TotalEpisode" => substr($SubDetail01[6], strpos($SubDetail01[6], ":") + 1),
                                "HariTayang" => substr($SubDetail01[7], strpos($SubDetail01[7], ":") + 1),
                            );
                            $href = $node->filter('.col-md-3 > a')->attr("href");
                            $SubListDetail = array(
                                "subDetail" => $SubDetail02,
                                "synopsis" => $synopsis,
                                "genre" => $genre,
                                "hrefEpisode" => $href
                            );
                            return $SubListDetail; 
                        });
                    }
                    $SingleEpisode[]=array(
                        "SingleEpisode"=>$SubListDetail
                    );
                    
                }
                
                $dataPage = $crawler->filter('.pagination')->html();
                $TotalSearchPage = $this->FilterPageEpisode($dataPage);
                if(!is_numeric($TotalSearchPage)){
                    $TotalSearchPage = 1;
                }
                
                if($PageNumber <= $TotalSearchPage){
                    $A =0;
                    for($i=0;$i < count($SingleEpisode);$i++){
                        $href = $BASE_URL."".$SingleEpisode[$i]['SingleEpisode'][0]['hrefEpisode'];
                        $Image = $LastUpdateEps[0][$i]['image'];
                        $Title = $LastUpdateEps[0][$i]['title'];
                        $TitleAlias = $LastUpdateEps[0][$i]['titleAlias'];
                        $hrefSingleList = $LastUpdateEps[0][$i]['hrefSingleList'];
                        $Title = Converter::__normalizeNameEpsChar($Title,$hrefSingleList);
                        $TitleAlias = Converter::__normalizeNameEpsChar($TitleAlias,$hrefSingleList);
                        
                        if($Title == "Email"){
                            $Title = Converter::__normalizeNameEps($LastUpdateEps[0][$i]['hrefSingleList']);
                            $TitleAlias = Converter::__normalizeNameEps($LastUpdateEps[0][$i]['hrefSingleList']);
                        }
                        $TotalEpisode = $SingleEpisode[$i]['SingleEpisode'][0]['subDetail']['TotalEpisode'];
                        $Rating = $SingleEpisode[$i]['SingleEpisode'][0]['subDetail']['Rating'];
                        $Synopsis = $SingleEpisode[$i]['SingleEpisode'][0]['synopsis'];
                        $GenreList = $SingleEpisode[$i]['SingleEpisode'][0]['genre'];
                        $Years = '';
                        
                        $Status = $LastUpdateEps[0][$i]['status'];
                        $Episode = $LastUpdateEps[0][$i]['episode'];
                        $KeyEpisodeEnc=array(
                            "href" => $href,
                            "Image" => $Image,
                            "Title" => $Title,
                            "Status" => $Status,
                            "Episode" => $Episode
                        );
                        
                        $KeyEpisode = EnkripsiData::encodeKeyEpisodeAnime($KeyEpisodeEnc);
                        
                        {#save Data to mysql
                            $Slug = (Str::slug($Title)."-".Str::slug($Episode));
                            $code = $Slug;
                            $cdListAnime = Str::slug($Title);
                            
                            {#Save to List anime
                                $codeListAnime['code'] = md5($cdListAnime);
                                $listAnime = MainModel::getDataListAnime($codeListAnime);
                                $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                if(empty($listAnime) || $idListAnime == 0){
                                    
                                    $slugListAnime = $cdListAnime;
                                    $KeyListAnimEnc= array(
                                        "Title"=>trim($Title),
                                        "Image"=>"",
                                        "Type"=>"",
                                        "href"=>$hrefKeyListAnim[$i]
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
                                    }
                                    $codeListAnime['code'] = md5($cdListAnime);
                                    $listAnime = MainModel::getDataListAnime($codeListAnime);
                                    $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                                }
                            }#End Save to Liat anime

                            {#save to detail anime and list episode
                                $hrefEpisode = ($SingleEpisode[$i]['SingleEpisode'][0]['hrefEpisode']);
                                $slugListEps = Converter::__filterCodeEpisodeAnime($hrefEpisode);
                                $codeListEps['code'] = md5($slugListEps);
                                $codeDetailAnime['code'] = md5($cdListAnime);
                                $SlugDetailAnime = $cdListAnime;
                                $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                                $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                                $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                                $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                                if(empty($DetailAnime) || $idDetailAnime == 0 || $idListEpisode == 0 || empty($ListEpisodeAnime) ){
                                    $KeyListAnimEnc= array(
                                        "Title"=>trim($Title),
                                        "Image"=>"",
                                        "Type"=>"",
                                        "href"=>$hrefKeyListAnim[$i]
                                    );
                                    $KeyListAnim = EnkripsiData::encodeKeyListAnime($KeyListAnimEnc);
                                    $listDataAnime = [
                                        'params' => [
                                            'X-API-KEY' => env('X_API_KEY',''),
                                            'KeyListAnim' => $KeyListAnim
                                        ]
                                    ];
                                    $dataDetailAnime = $this->DetailListAnimeController->DetailListAnim(NULL,$listDataAnime);
                                    $codeDetailAnime['code'] = md5($cdListAnime);
                                }
                                $hrefEpisode = ($SingleEpisode[$i]['SingleEpisode'][0]['hrefEpisode']);
                                $slugListEps = Converter::__filterCodeEpisodeAnime($hrefEpisode);
                                $codeListEps['code'] = md5($slugListEps);
                                $codeDetailAnime['code'] = md5($cdListAnime);
                                $SlugDetailAnime = $cdListAnime;
                                $DetailAnime = MainModel::getDataDetailAnime($codeDetailAnime);
                                $ListEpisodeAnime = MainModel::getDataListEpisodeAnime($codeListEps);
                                $idDetailAnime = (empty($DetailAnime)) ? 0 : $DetailAnime[0]['id'];
                                $idListEpisode = (empty($ListEpisodeAnime)) ? 0 : $ListEpisodeAnime[0]['id'];
                            }#End #save to detail anime and list episode

                            {#save to Data Last Update
                                $paramCheck['code'] = md5($code);
                                $checkExist = MainModel::getDataLastUpdate($paramCheck);
                                if(empty($checkExist)){
                                    $Input = array(
                                        'code' => md5($code),
                                        'slug' => $Slug,
                                        'id_list_anime' => $idListAnime,
                                        'id_list_episode' => $idListEpisode,
                                        'id_detail_anime' => $idDetailAnime,
                                        "image" => $Image,
                                        "title" => $Title,
                                        "title_alias" => $TitleAlias,
                                        "status" => $Status,
                                        "episode" => $Episode,
                                        "keyepisode" => $KeyEpisode,
                                        'total_search_page' => $TotalSearchPage,
                                        'page_search' => $PageNumber,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Save - ".$Slug;
                                    $save = MainModel::insertLastUpdateMysql($Input);
                                }else{
                                    $conditions['id'] = $checkExist[0]['id'];
                                    $Update = array(
                                        'code' => md5($code),
                                        'slug' => $Slug,
                                        'id_list_anime' => $idListAnime,
                                        'id_list_episode' => $idListEpisode,
                                        'id_detail_anime' => $idDetailAnime,
                                        "image" => $Image,
                                        "title" => $Title,
                                        "title_alias" => $TitleAlias,
                                        "status" => $Status,
                                        "episode" => $Episode,
                                        "keyepisode" => $KeyEpisode,
                                        'total_search_page' => $TotalSearchPage,
                                        'page_search' => $PageNumber,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] =  "Data Update - ".$Slug;
                                    $save = MainModel::updateLastUpdateMysql($Update,$conditions);
                                }
                            }#End save to Data Last Update

                        }#End save Data to mysql   
                    }
                    return ResponseConnected::Success("Last Update Anime", $save, $LogSave, $awal);
                }else{
                    return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
                }
            }else{
                return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
            }
        }else{
            return ResponseConnected::PageNotFound("Last Update Anime","Page Not Found.", $awal);
        }
    }
    
    // ================ End  LastUpdateAnime Save To Mysql =========================================

    /**
     * @author [Prayugo]
     * @create date 2020-01-25 00:03:55
     * @desc [generateLastUpdateAnime]
     */
    // ================  generateLastUpdateAnime Save To Mongo =========================================
    public function generateLastUpdateAnime(Request $request = NULL, $params = NULL){
        $param = $params; # get param dari populartopiclist atau dari cron
        if(is_null($params)) $param = $request->all();

        $id = (isset($param['params']['id']) ? $param['params']['id'] : NULL);
        $code = (isset($param['params']['code']) ? $param['params']['code'] : '');
        $slug = (isset($param['params']['slug']) ? $param['params']['slug'] : '');
        $title = (isset($param['params']['title']) ? $param['params']['title'] : '');
        $startNameIndex = (isset($param['params']['start_name_index']) ? $param['params']['start_name_index'] : '');
        $endNameIndex = (isset($param['params']['end_name_index']) ? $param['params']['end_name_index'] : '');
        $startDate = (isset($param['params']['start_date']) ? $param['params']['start_date'] : NULL);
        $endDate = (isset($param['params']['end_date']) ? $param['params']['end_date'] : NULL);
        $isUpdated = (isset($param['params']['is_updated']) ? filter_var($param['params']['is_updated'], FILTER_VALIDATE_BOOLEAN) : FALSE);

        
    }
    // ================ End generateLastUpdateAnime Save To Mysql =========================================

}