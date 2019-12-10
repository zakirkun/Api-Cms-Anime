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

#Load Models V1
use App\Models\V1\MainModel as MainModel;

// done tinggal token db
class LastUpdateEpsAnimController extends Controller
{
    public function LastUpdateAnime(Request $request){

        function __construct(){
            $this->MappingMysql = new MappingMysql();
            
        }
        
        $ApiKey=$request->header("X-API-KEY");
        $PageNumber=$request->header("PageNumber") ? $request->header("PageNumber") : 1;
        $Users = MainModel::getUser($ApiKey);
        $Token = $Users[0]['token'];
        if($Token){
            try{
                $ConfigController = new ConfigController();
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                if($PageNumber<2){
                    $BASE_URL_LIST=$BASE_URL;
                }else{
                    $BASE_URL_LIST=$BASE_URL."/?page=".$PageNumber;
                }
                return $this->LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL);
            }catch(\Exception $e){
                return $this->InternalServerError();
            }
            
        }else{
            return $this->InvalidToken();
        }

        
    }
    
    public function Success($Save,$LogSave){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Last Update Anime",
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

    public function InternalServerError(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Last Update Anime",
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
    public function PageNotFound(){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>"Last Update Anime",
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
                "NameEnd"=>"Last Update Anime",
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

    public function LastUpdateAnimValue($PageNumber,$BASE_URL_LIST,$BASE_URL){
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
                $SingleEpisode=array();
                for($i=0;$i<count($LastUpdateEps[0]);$i++){
                    $SingleListHref=$BASE_URL."".$LastUpdateEps[0][$i]['hrefSingleList'];
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
                                "Judul"=>substr($SubDetail01[1], strpos($SubDetail01[1], ":") + 1),
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
                                $genre[] = substr($detGenre[$j], strpos($detGenre[$j], ">") + 1);
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
                    for($i=0;$i < count($SingleEpisode);$i++){
                        $href = $BASE_URL."".$SingleEpisode[$i]['SingleEpisode'][0]['hrefEpisode'];
                        $Image = $LastUpdateEps[0][$i]['image'];
                        $Title = $LastUpdateEps[0][$i]['title'];
                        $TitleAlias = $LastUpdateEps[0][$i]['titleAlias'];
                        $Status = $LastUpdateEps[0][$i]['status'];
                        $Episode = $LastUpdateEps[0][$i]['episode'];
                        $KeyEpisodeEnc=array(
                            "href" => $href,
                            "Image" => $Image,
                            "Title" => $Title,
                            "Status" => $Status,
                            "Episode" => $Episode
                        );
                        $result = base64_encode(json_encode($KeyEpisodeEnc));
                        $result = str_replace("=", "QRCAbuK", $result);
                        $iduniq0 = substr($result, 0, 10);
                        $iduniq1 = substr($result, 10, 500);
                        $result = $iduniq0 . "QtYWL" . $iduniq1;
                        $KeyEpisode = $result;
                        $paramCheck['code'] = md5(Str::slug($Title)."-".Str::slug($Episode));
                        $codeListAnime['code'] = md5($Title);
                        $checkExist = MainModel::getDataLastUpdate($paramCheck);
                        $listAnime = MainModel::getDataListAnime($codeListAnime);
                        $idListAnime = (empty($listAnime)) ? 0 : $listAnime[0]['id'];
                        
                        if(empty($checkExist)){
                            $Input = array(
                                "image" => $Image,
                                "title" => $Title,
                                "title_alias" => $TitleAlias,
                                "status" => $Status,
                                "episode" => $Episode,
                                "keyepisode" => $KeyEpisode,
                                'total_search_page' => $TotalSearchPage,
                                'page_search' => $PageNumber,
                                'slug' => Str::slug($Title),
                                'id_list_anime' => $idListAnime,
                                'code' => md5(Str::slug($Title)."-".Str::slug($Episode)),
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            $LogSave [] = "Data Save - ".$Title." ".$Episode;
                            $save = MainModel::insertLastUpdateMysql($Input);
                        }else{
                            $conditions['id'] = $checkExist[0]['id'];
                            $Update = array(
                                "image" => $Image,
                                "title" => $Title,
                                "title_alias" => $TitleAlias,
                                "status" => $Status,
                                "episode" => $Episode,
                                "keyepisode" => $KeyEpisode,
                                'total_search_page' => $TotalSearchPage,
                                'page_search' => $PageNumber,
                                'slug' => Str::slug($Title),
                                'id_list_anime' => $idListAnime,
                                'code' => md5(Str::slug($Title)."-".Str::slug($Episode)),
                                'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                            );
                            $LogSave [] =  "Data Update - ".$Title." ".$Episode;
                            $save = MainModel::updateLastUpdateMysql($Update,$conditions);
                        }
                        
                    }
                    
                    return $this->Success($save,$LogSave);
                }else{
                    return $this->PageNotFound();
                }
                
            }else{
                return $this->PageNotFound();
            }
        }else{
            return $this->PageNotFound();
        }
    }
}