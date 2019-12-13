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
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'last_update';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
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
        $tabel_name = 'list_anime';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;

    }
    #================ End getDataListAnime ==================================

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
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'genre_list';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
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
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'trending_week';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
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
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'detail_anime';
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
     * @desc function getDataListEpisoeAnime
     */
    #================  getDataListEpisoeAnime ==================================
    public static function getDataListEpisoeAnime($params = []){
        $code = (isset($params['code']) ? $params['code'] : '');
        $tabel_name = 'list_episode';
        ini_set('memory_limit','1024M');
        $query = DB::connection('application_db')
            ->table($tabel_name);
        
        if(!empty($code)) $query = $query->where('code', '=', $code);
        $query = $query->get();

        $result = [];
        if(count($query)) $result = collect($query)->map(function($x){ return (array) $x; })->toArray();
        return $result;
    }
    #================ End getDataListEpisoeAnime ==================================
    
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

}