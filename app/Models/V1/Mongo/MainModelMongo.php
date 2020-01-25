<?php

namespace App\Models\V1\Mongo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#Load Component External
use Cache;
use Config;
use Carbon\Carbon;

#Load Helpers V1

#Load Collection V1
use App\Models\V1\Mongo\CollectionListAnimeModel;
use App\Models\V1\Mongo\CollectionDetailAnimeModel;
use App\Models\V1\Mongo\CollectionLastUpdateModel;
use App\Models\V1\Mongo\CollectionStreamAnimeModel;
use App\Models\V1\Mongo\CollectionTrendingWeekModel;


class MainModelMongo extends Model
{
    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [updateListAnime]
     */
    // ====================================== updateListAnime ======================================================================
    static function updateListAnime($data = [], $collections = NULL, $conditions = [], $upsert = TRUE, $database_name = 'mongodb'){
        if($upsert == TRUE){ #USE UPSERT
            $query = CollectionListAnimeModel::on($database_name)->raw(function($collection) use ($conditions, $data)
            {
                return $collection->updateOne(
                $conditions,
                ['$set' => $data],
                ['upsert' => true]);
            });

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0 || $query->getUpsertedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }else{
            $query = CollectionListAnimeModel::on($database_name)->raw()->updateOne(
                $conditions,
                ['$set' => $data],
                ['w' => 'majority']
            );

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }
        return $result;
    }
    // ====================================== End updateListAnime ======================================================================

    static function deleteListAnime($collections = NULL, $conditions = [], $database_name = 'mongodb'){
        $query = DB::connection($database_name)
            ->collection($collections);

        foreach($conditions as $key => $value){ #jika di ditemukan datanya maka update, jika tidak insert (upsert)
            $query = $query->where($key, $value);
        }

        $query = $query->delete();

        $data['status'] = 400;
        $data['message'] = 'Gagal Delete';
        if($query){
            $data['status'] = 200;
            $data['message'] = 'Berhasil Delete';
        }

        return $data;
    }

    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [getListAnimeMongo]
     */
    // ====================================== getListAnimeMongo ======================================================================
    static function getListAnimeMongo($params = [], $database_name = 'mongodb'){

        $timeout = Config::get('general_config.mongo.query_timeout');

        $id_auto = (isset($params['id_auto']) ? $params['id_auto'] : NULL);

        $query = CollectionListAnimeModel::on($database_name)
            ->timeout($timeout)
            ->where('id_auto', '=', $id_auto)
            ->first();

        $result = [];
        if(!empty($query)){
            $result = $query->toArray();
        }

        return $result;
    }
    // ====================================== End getListAnimeMongo ======================================================================

    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [updateLastUpdateAnime]
     */
    // ====================================== updateLastUpdateAnime ======================================================================
    static function updateLastUpdateAnime($data = [], $collections = NULL, $conditions = [], $upsert = TRUE, $database_name = 'mongodb'){
        if($upsert == TRUE){ #USE UPSERT
            $query = CollectionListAnimeModel::on($database_name)->raw(function($collection) use ($conditions, $data)
            {
                return $collection->updateOne(
                $conditions,
                ['$set' => $data],
                ['upsert' => true]);
            });

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0 || $query->getUpsertedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }else{
            $query = CollectionListAnimeModel::on($database_name)->raw()->updateOne(
                $conditions,
                ['$set' => $data],
                ['w' => 'majority']
            );

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }
        return $result;
    }
    // ====================================== End updateLastUpdateAnime ======================================================================

    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [updateDetailListAnime]
     */
    // ====================================== updateDetailListAnime ======================================================================
    static function updateDetailListAnime($data = [], $collections = NULL, $conditions = [], $upsert = TRUE, $database_name = 'mongodb'){
        if($upsert == TRUE){ #USE UPSERT
            $query = CollectionDetailAnimeModel::on($database_name)->raw(function($collection) use ($conditions, $data)
            {
                return $collection->updateOne(
                $conditions,
                ['$set' => $data],
                ['upsert' => true]);
            });

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0 || $query->getUpsertedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }else{
            $query = CollectionDetailAnimeModel::on($database_name)->raw()->updateOne(
                $conditions,
                ['$set' => $data],
                ['w' => 'majority']
            );

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }
        return $result;
    }
    // ====================================== End updateDetailListAnime ======================================================================

    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [updateStreamAnime]
     */
    // ====================================== updateStreamAnime ======================================================================
    static function updateStreamAnime($data = [], $collections = NULL, $conditions = [], $upsert = TRUE, $database_name = 'mongodb'){
        if($upsert == TRUE){ #USE UPSERT
            $query = CollectionStreamAnimeModel::on($database_name)->raw(function($collection) use ($conditions, $data)
            {
                return $collection->updateOne(
                $conditions,
                ['$set' => $data],
                ['upsert' => true]);
            });

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0 || $query->getUpsertedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }else{
            $query = CollectionStreamAnimeModel::on($database_name)->raw()->updateOne(
                $conditions,
                ['$set' => $data],
                ['w' => 'majority']
            );

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }
        return $result;
    }
    // ====================================== End updateStreamAnime ======================================================================

    /**
     * @author [prayugo]
     * @create date 2020-01-25 22:46:47
     * @desc [updateTrendingWeekAnime]
     */
    // ====================================== updateTrendingWeekAnime ======================================================================
    static function updateTrendingWeekAnime($data = [], $collections = NULL, $conditions = [], $upsert = TRUE, $database_name = 'mongodb'){
        if($upsert == TRUE){ #USE UPSERT
            $query = CollectionTrendingWeekModel::on($database_name)->raw(function($collection) use ($conditions, $data)
            {
                return $collection->updateOne(
                $conditions,
                ['$set' => $data],
                ['upsert' => true]);
            });

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0 || $query->getUpsertedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }else{
            $query = CollectionTrendingWeekModel::on($database_name)->raw()->updateOne(
                $conditions,
                ['$set' => $data],
                ['w' => 'majority']
            );

            if($query->isAcknowledged() == TRUE){
                if($query->getModifiedCount() > 0){
                    $result['status'] = 200;
                    $result['message'] = 'Berhasil Update';
                    $result['message_local'] = 'Berhasil Update';
                }else{
                    $result['status'] = 200;
                    $result['message'] = 'Gagal Update';
                    $result['message_local'] = 'Match 1, Update 0';
                }
            }else{
                $result['status'] = 400;
                $result['message'] = 'Gagal Update';
                $result['message_local'] = 'Gagal Update';
            }
        }
        return $result;
    }
    // ====================================== End updateTrendingWeekAnime ======================================================================
}