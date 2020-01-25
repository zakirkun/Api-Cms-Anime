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


class GenreListAnimeController extends Controller
{
    public function GenreListAnime(Request $request = NULL, $params = NULL){
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
                $BASE_URL_LIST=$ConfigController->BASE_URL_ANIME_1."/archive/genre/";
                $BASE_URL=$ConfigController->BASE_URL_ANIME_1;
                return $this->GenreListAnimValue($BASE_URL_LIST,$BASE_URL,$awal);
            // }catch(\Exception $e){
            //     return ResponseConnected::InternalServerError("Genre Anime","Internal Server Error",$awal);
            // }
            
        }else{
            return ResponseConnected::InvalidToken("Genre Anime","Invalid Token", $awal);
        }
    }

    public function GenreListAnimValue($BASE_URL_LIST,$BASE_URL,$awal){
        $client = new Client(['cookies' => new FileCookieJar('cookies.txt')]);
        $client->getConfig('handler')->push(CloudflareMiddleware::create());
        $goutteClient = new GoutteClient();
        $goutteClient->setClient($client);
        $crawler = $goutteClient->request('GET', $BASE_URL_LIST);
        $response = $goutteClient->getResponse();
        $status = $response->getStatus();
        if($status == 200){
            // Get the latest post in this category and display the titles
            $GenreListAnimeS= $crawler->filter('.single')->each(function ($node,$i) {
                
                $subGenre= $node->filter('.archiveTags')->each(function ($node,$i) {
                    $genre = $node->filter('a')->text('Default text content');
                    $href = $node->filter('a')->attr('href');
                    $subGenre = [
                        "genre"=>$genre,
                        "href"=>$href
                    ];
                    return $subGenre;
                });

                $GenreList = [
                    "subGenre"=>$subGenre
                ];
                return $GenreList;
            });
            if($GenreListAnimeS){
                
                for($i=0;$i<count($GenreListAnimeS[0]['subGenre']);$i++){
                    $NameIndex[]=substr($GenreListAnimeS[0]['subGenre'][$i]['genre'],0,1);
                }
                $NameIndex=array_values(array_unique($NameIndex));
                unset($NameIndex[0]);
                for($i=1;$i<=count($NameIndex);$i++){
                    
                    $ListSubIndex=array();
                    for($j=0;$j<count($GenreListAnimeS[0]['subGenre']);$j++){
                        $NameFilter=substr($GenreListAnimeS[0]['subGenre'][$j]['genre'],0,1);
                        if($NameIndex[$i]==$NameFilter){
                            $KeyListGenreEnc= array(
                                "Genre"=>$GenreListAnimeS[0]['subGenre'][$j]['genre'],
                                "href"=>$BASE_URL."".$GenreListAnimeS[0]['subGenre'][$j]['href']
                            );
                            
                            $KeyListGenre = EnkripsiData::encodeKeyListGenre($KeyListGenreEnc);
                            $Genre = $GenreListAnimeS[0]['subGenre'][$j]['genre'];
                            
                            {#Save Data List Genre
                                $code = Str::slug($Genre);
                                $paramCheck['code'] = md5($code);
                                $checkExist = MainModel::getDataListGenre($paramCheck);
                                if(empty($checkExist)){
                                    $Input = array(
                                        "code" => md5($code),
                                        'slug' => Str::slug($Genre),
                                        "name_index" => $NameIndex[$i],
                                        "genre" => $Genre,
                                        "key_list_genre" => $KeyListGenre,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Save - ".$Genre."-".Carbon::now()->format('Y-m-d H:i:s');
                                    $save = MainModel::insertGenreListAnimeMysql($Input);
                                }else{
                                    $conditions['id'] = $checkExist[0]['id'];
                                    $Update = array(
                                        "code" => md5($code),
                                        'slug' => Str::slug($Genre),
                                        "name_index" => $NameIndex[$i],
                                        "genre" => $Genre,
                                        "key_list_genre" => $KeyListGenre,
                                        'cron_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    );
                                    $LogSave [] = "Data Update - ".$Genre."-".Carbon::now()->format('Y-m-d H:i:s');
                                    $save = MainModel::updateGenreListAnimeMysql($Update,$conditions);
                                }
                            }#End Data List Genre
                            
                        }
                    }
                    
                }
                
                return ResponseConnected::Success("Genre Anime", $save, $LogSave, $awal);
            }else{
                return ResponseConnected::PageNotFound("Genre Anime","Page Not Found.", $awal);
            }
        }else{
            return ResponseConnected::PageNotFound("Genre Anime","Page Not Found.", $awal);
        }
    }

}