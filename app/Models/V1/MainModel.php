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
}