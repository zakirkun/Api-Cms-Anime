<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#Load Component External
use Cache;
use Config;
use Carbon\Carbon;

#Load Helpers V1

#Load Collection V1

class MainModel extends Model
{
    /**
     * @author [Prayugo]
     * @create date 2019-12-08 23:28:51
     * @modify date 2019-12-08 23:28:51
     * @desc function getUser
     */
    #================ getUser ==================================
    static function getUser($ApiKey){
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table('User')
            ->where('token', $ApiKey);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getUser ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 23:28:51
     * @modify date 2019-12-09 23:28:51
     * @desc function insertLastUpdateMysql
     */
    #================  insertLastUpdateMysql ==================================
    public static function insertLastUpdateMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'last_update';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertLastUpdateMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 00:28:51
     * @modify date 2019-12-09 00:28:51
     * @desc function updateLastUpdateMysql
     */
    #================  updateLastUpdateMysql ==================================
    public static function updateLastUpdateMysql($data_all = [], $conditions){
        $tabel_name = 'last_update';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateLastUpdateMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function getDataLastUpdate
     */
    #================  getDataLastUpdate ==================================
    static function getDataLastUpdate($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $Title = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        $lastDate = (isset($params['last_date']) ? filter_var($params['last_date'], FILTER_VALIDATE_BOOLEAN) : FALSE);

        $tabel_name = 'last_update';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if($lastDate) $query = $query->orderBy('cron_at', 'DESC');
        if(!empty($ID)) $query = $query->where('id', '=', $ID);    
        if(!empty($Slug)) $query = $query->where('slug', '=', $Slug);    
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($Title)) $query = $query->where('title', 'Like', "%".$Title);

        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }

        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;

    }
    #================ End getDataLastUpdate ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function getDataListAnime
     */
    #================  getDataListAnime ==================================
    static function getDataListAnime($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $startByIndex = (isset($params['start_by_index']) ? $params['start_by_index'] : '');
        $EndByIndex = (isset($params['end_by_index']) ? $params['end_by_index'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');

        $tabel_name = 'list_anime';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($startByIndex)) $query = $query->where('name_index', 'Like', "%".$startByIndex);
        if(!empty($EndByIndex)){
            $alphas = range($startByIndex, $EndByIndex);
            for($i = 0;$i<count($alphas); $i++){
                $query = $query->orWhere('name_index', 'Like', "%".$alphas[$i]);  
            }
        } 
        if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
        if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;

    }
    #================ End getDataListAnime ==================================
    /**
     * @author [Prayugo]
     * @create date 2020-01-12 21:28:51
     * @desc function getDataListAnimeJoin
     */
    #================  getDataListAnimeJoin ==================================
    static function getDataListAnimeJoin($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $Title = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $startByIndex = (isset($params['start_by_index']) ? $params['start_by_index'] : '');
        $EndByIndex = (isset($params['end_by_index']) ? $params['end_by_index'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir

        $tabel_list_anime = 'list_anime as ';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table('list_anime as LA')
            ->select([
                'LA.id', 'LA.code', 'LA.slug', 'LA.title', 'LA.name_index','LA.cron_at',
                'DA.image', 'DA.status', 'DA.id as id_detail_anime', 'DA.rating', 'DA.genre'
            ])
            ->leftJoin('detail_anime AS DA', 'LA.id', '=', 'DA.id_list_anime');
        
        if(!empty($ID)) $query = $query->where('LA.id', '=', $ID);    
        if(!empty($Slug)) $query = $query->where('LA.slug', '=', $Slug);    
        if(!empty($code)) $query = $query->where('LA.code', '=', $code);
        if(!empty($Title)) $query = $query->where('LA.title', 'Like', "%".$Title);
        if(!empty($startByIndex)) $query = $query->where('LA.name_index', 'Like', "%".$startByIndex);
        if(!empty($EndByIndex)){
            $alphas = range($startByIndex, $EndByIndex);
            for($i = 0;$i < count($alphas); $i++){
                $query = $query->orWhere('LA.name_index', 'Like', "%".$alphas[$i]);  
            }
        }
        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('LA.cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('LA.cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('LA.cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;

    }
    #================ End getDataListAnimeJoin ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function insertListAnimeMysql
     */
    #================  insertListAnimeMysql ==================================
    public static function insertListAnimeMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'list_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertListAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function updateListAnimeMysql
     */
    #================  updateListAnimeMysql ==================================
    public static function updateListAnimeMysql($data_all = [], $conditions){
        $tabel_name = 'list_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateListAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function getDataListGenre
     */
    #================  getDataListGenre ==================================
    public static function getDataListGenre($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $genre = (isset($params['genre']) ? $params['genre'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $startByIndex = (isset($params['start_by_index']) ? $params['start_by_index'] : '');
        $EndByIndex = (isset($params['end_by_index']) ? $params['end_by_index'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir

        $tabel_name = 'genre_list';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($ID)) $query = $query->where('id', '=', $ID);    
        if(!empty($Slug)) $query = $query->where('slug', '=', $Slug);    
        if(!empty($genre)) $query = $query->where('genre', 'Like', "%".$genre);
        if(!empty($startByIndex)) $query = $query->where('name_index', 'Like', "%".$startByIndex);
        if(!empty($EndByIndex)){
            $alphas = range($startByIndex, $EndByIndex);
            for($i = 0;$i < count($alphas); $i++){
                $query = $query->orWhere('name_index', 'Like', "%".$alphas[$i]);  
            }
        }
        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataListGenre ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function getDataListGenre
     */
    #================  insertGenreListAnimeMysql ==================================
    public static function insertGenreListAnimeMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'genre_list';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertGenreListAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-09 01:28:51
     * @modify date 2019-12-09 01:28:51
     * @desc function updateGenreListAnimeMysql
     */
    #================  updateGenreListAnimeMysql ==================================
    public static function updateGenreListAnimeMysql($data_all = [], $conditions){
        $tabel_name = 'genre_list';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateGenreListAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-11 23:28:51
     * @modify date 2019-12-11 23:28:51
     * @desc function getDataTrendingWeek
     */
    #================  getDataTrendingWeek ==================================
    public static function getDataTrendingWeek($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $Title = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        
        $tabel_name = 'trending_week';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($ID)) $query = $query->where('id', '=', $ID);    
        if(!empty($Slug)) $query = $query->where('slug', '=', $Slug);    
        if(!empty($Title)) $query = $query->where('title', 'Like', "%".$Title);

        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataTrendingWeek ==================================
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-11 01:28:51
     * @modify date 2019-12-11 01:28:51
     * @desc function insertTrendingWeekMysql
     */
    #================  insertTrendingWeekMysql ==================================
    public static function insertTrendingWeekMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'trending_week';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertTrendingWeekMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-11 01:28:51
     * @modify date 2019-12-11 01:28:51
     * @desc function updateTrendingWeekMysql
     */
    #================  updateTrendingWeekMysql ==================================
    public static function updateTrendingWeekMysql($data_all = [], $conditions){
        $tabel_name = 'trending_week';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateTrendingWeekMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 20:28:51
     * @modify date 2019-12-13 20:28:51
     * @desc function getDataScheduleAnime
     */
    #================  getDataScheduleAnime ==================================
    public static function getDataScheduleAnime($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'schedule';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataScheduleAnime ==================================
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-13 20:28:51
     * @modify date 2019-12-13 20:28:51
     * @desc function insertScheduleMysql
     */
    #================  insertScheduleMysql ==================================
    public static function insertScheduleMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'schedule';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertScheduleMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 20:28:51
     * @modify date 2019-12-13 20:28:51
     * @desc function updateScheduleWeekMysql
     */
    #================  updateScheduleWeekMysql ==================================
    public static function updateScheduleWeekMysql($data_all = [], $conditions){
        $tabel_name = 'schedule';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateScheduleWeekMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function getDataDetailAnime
     */
    #================  getDataDetailAnime ==================================
    public static function getDataDetailAnime($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $idListAnime = (isset($params['id_list_anime']) ? $params['id_list_anime'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $Title = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir

        $tabel_name = 'detail_anime';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($ID)) $query = $query->where('id', '=', $ID);    
        if(!empty($idListAnime)) $query = $query->where('id_list_anime', '=', $idListAnime);    
        if(!empty($Slug)) $query = $query->where('slug', '=', $Slug);    
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($Title)) $query = $query->where('title', 'Like', "%".$Title);
        
        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataScheduleAnime ==================================
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function insertScheduleMysql
     */
    #================  insertScheduleMysql ==================================
    public static function insertDetailMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'detail_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertDetailMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function updateDetailMysql
     */
    #================  updateDetailMysql ==================================
    public static function updateDetailMysql($data_all = [], $conditions){
        $tabel_name = 'detail_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateDetailMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function getDataListEpisodeAnime
     */
    #================  getDataListEpisodeAnime ==================================
    public static function getDataListEpisodeAnime($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $id = (isset($params['id']) ? $params['id'] : '');
        $id_detail_anime = (isset($params['id_detail_anime']) ? $params['id_detail_anime'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        
        $tabel_name = 'list_episode';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($id)) $query = $query->where('id', '=', $id);
        if(!empty($id_detail_anime)) $query = $query->where('id_detail_anime', '=', $id_detail_anime);

        if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
        if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataListEpisodeAnime ==================================

    public static function getDataListEpisodeJoin($params = []){
        $ID = (isset($params['id']) ? $params['id'] : '');
        $id_detail_anime = (isset($params['id_detail_anime']) ? $params['id_detail_anime'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $episode = (isset($params['episode']) ? $params['episode'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $startByIndex = (isset($params['start_by_index']) ? $params['start_by_index'] : '');
        $EndByIndex = (isset($params['end_by_index']) ? $params['end_by_index'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir

        $tabel_list_anime = 'list_anime as ';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table('list_episode as LE')
            ->select([
                'LE.id as id_list_episode', 'LE.id_detail_anime', 'LE.slug', 'LE.episode',
                'SA.id as id_stream_anime', 'SA.title'
            ])
            ->leftJoin('stream_anime AS SA', 'LE.id', '=', 'SA.id_list_episode');
        
        if(!empty($ID)) $query = $query->where('LE.id', '=', $ID);    
        if(!empty($id_detail_anime)) $query = $query->where('LE.id_detail_anime', '=', $id_detail_anime);    
        if(!empty($Slug)) $query = $query->where('LE.slug', '=', $Slug);    
        if(!empty($code)) $query = $query->where('LE.code', '=', $code);
        if(!empty($episode)) $query = $query->where('LE.episode', 'Like', "%".$episode);
        

        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('LA.cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('LA.cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('LA.cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function insertListEpisodelMysql
     */
    #================  insertListEpisodelMysql ==================================
    public static function insertListEpisodelMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'list_episode';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertListEpisodelMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-13 22:28:51
     * @modify date 2019-12-13 22:28:51
     * @desc function updateListEpisodeMysql
     */
    #================  updateListEpisodeMysql ==================================
    public static function updateListEpisodeMysql($data_all = [], $conditions){
        $tabel_name = 'list_episode';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateListEpisodeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function getStreamAnime
     */
    #================  getStreamAnime ==================================
    public static function getStreamAnime($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $ID = (isset($params['id']) ? $params['id'] : '');
        $idListEpisode = (isset($params['id_list_episode']) ? $params['id_list_episode'] : '');
        $idDetailAnime = (isset($params['id_detail_anime']) ? $params['id_detail_anime'] : '');
        $idListAnime = (isset($params['id_list_anime']) ? $params['id_list_anime'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $episode = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $title = (isset($params['title']) ? $params['title'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir

        $tabel_name = 'stream_anime';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($ID)) $query = $query->where('id', '=', $ID);
        if(!empty($idListEpisode)) $query = $query->where('id_list_episode', '=', $idListEpisode);
        if(!empty($idDetailAnime)) $query = $query->where('iid_detail_animed', '=', $idDetailAnime);
        if(!empty($idListEpisode)) $query = $query->where('id_list_anime', '=', $idListEpisode);
        if(!empty($Slug)) $query = $query->where('slug', '=', $Slug);    
        if(!empty($episode)) $query = $query->where('title', 'Like', "%".$episode);
        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getStreamAnime ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function getStreamAnimeJoin
     */
    #================  getStreamAnimeJoin ==================================
    public static function getStreamAnimeJoin($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $ID = (isset($params['id']) ? $params['id'] : '');
        $idListEpisode = (isset($params['id_list_episode']) ? $params['id_list_episode'] : '');
        $idDetailAnime = (isset($params['id_detail_anime']) ? $params['id_detail_anime'] : '');
        $idListAnime = (isset($params['id_list_anime']) ? $params['id_list_anime'] : '');
        $code = (isset($params['code']) ? $params['code'] : '');
        $episode = (isset($params['title']) ? $params['title'] : '');
        $Slug = (isset($params['slug']) ? $params['slug'] : '');
        $title = (isset($params['title']) ? $params['title'] : '');
        $startDate = (isset($params['start_date']) ? $params['start_date'] : '');
        $endDate = (isset($params['end_date']) ? $params['end_date'] : '');
        $isUpdated = (isset($params['is_updated']) ? $params['is_updated'] : FALSE); #untuk data terbaru 2 jam terakhir
        
        $tabel_name = 'stream_anime';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table('stream_anime as  SA')
            ->select([
                'SA.id', 'SA.id_list_episode', 'SA.id_detail_anime', 
                'SA.id_list_anime', 'SA.code', 'SA.slug', 'SA.title','SA.cron_at',
                'DA.image', 'DA.status', 'DA.rating', 'DA.genre', 
                'DA.episode_total', 'score', 'rating','DA.synopsis'
            ])
            ->leftJoin('detail_anime AS DA', 'DA.id', '=', 'SA.id_detail_anime');
        
        if(!empty($code)) $query = $query->where('SA.code', '=', $code);
        if(!empty($ID)) $query = $query->where('SA.id', '=', $ID);
        if(!empty($idListEpisode)) $query = $query->where('SA.id_list_episode', '=', $idListEpisode);
        if(!empty($idDetailAnime)) $query = $query->where('SA.id_detail_anime', '=', $idDetailAnime);
        if(!empty($idListAnime)) $query = $query->where('SA.id_list_anime', '=', $idListAnime);
        if(!empty($Slug)) $query = $query->where('SA.slug', '=', $Slug);    
        if(!empty($episode)) $query = $query->where('SA.title', 'Like', "%".$episode);
        if($isUpdated){ #ambil data update atau terbaru
            $startDate = Carbon::parse($startDate)->timestamp;
            $endDate = Carbon::parse($endDate)->timestamp;
            $query = $query->whereBetween('SA.cron_at', [$startDate, $endDate]);
        }else{
            if(!empty($startDate) && empty($endDate)) $query = $query->where('SA.cron_at', '>=', $startDate);
            if($startDate && $endDate) $query = $query->whereBetween('SA.cron_at', [$startDate, $endDate]);
        }
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getStreamAnimeJoin ==================================
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function insertStreamAnimeMysql
     */
    #================  insertStreamAnimeMysql ==================================
    public static function insertStreamAnimeMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'stream_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertStreamAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function updateStreamAnimeMysql
     */
    #================  updateStreamAnimeMysql ==================================
    public static function updateStreamAnimeMysql($data_all = [], $conditions){
        $tabel_name = 'stream_anime';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateStreamAnimeMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function getServerStream
     */
    #================  getServerStream ==================================
    public static function getServerStream($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $id_stream_anime = (isset($params['id_stream_anime']) ? $params['id_stream_anime'] : '');
        $ID = (isset($params['id']) ? $params['id'] : '');
        $tabel_name = 'server_stream';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        if(!empty($id_stream_anime)) $query = $query->where('id_stream_anime', '=', $id_stream_anime);
        if(!empty($ID)) $query = $query->where('id', '=', $ID);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getServerStream ==================================
    
    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function insertServerStreamMysql
     */
    #================  insertServerStreamMysql ==================================
    public static function insertServerStreamMysql($data_all = [], $justInsert = FALSE){
        $tabel_name = 'server_stream';
        $query = DB::connection('application_db')
            ->table($tabel_name);
        if($justInsert){
            $query = $query->insert($data_all);
        }else{
            $query = $query->insertGetId($data_all);
        }
        $error = [];
        $data['status'] = 200;
        $data['message'] = 'success insert '.$tabel_name;
        if(!$query) {
            $data['status'] = 400;
            $data['message'] = 'failed insert '.$tabel_name;
            $error['msg'] = 'error insert '.$tabel_name;
            $error['num'] = 'error num insert '.$tabel_name;
        }

        $data['error'] 	= $error;
        if(!$justInsert) $data['id_result'] = $query;
        return $data;
    }
    #================ End insertServerStreamMysql ==================================

    /**
     * @author [Prayugo]
     * @create date 2019-12-16 02:28:51
     * @modify date 2019-12-16 02:28:51
     * @desc function updateServerStreamMysql
     */
    #================  updateServerStreamMysql ==================================
    public static function updateServerStreamMysql($data_all = [], $conditions){
        $tabel_name = 'server_stream';
        $query = DB::connection('application_db')
            ->table($tabel_name);

        foreach($conditions as $key => $value){
            $query = $query->where($key, $value);
        }
        $query = $query->update($data_all);
        $data['status'] = 400;
        $data['message'] = 'failed insert '.$tabel_name;
        if($query){
            $data['status'] = 200;
            $data['message'] = 'success insert '.$tabel_name;
        }
        return $data;
    }
    #================ End updateServerStreamMysql ==================================

}