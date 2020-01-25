<?php

namespace App\Models\V1\Mongo;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

use Config;

class CollectionDetailAnimeModel extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'contents';
    protected $primarykey = "_id";

    public function __construct() {
        $this->collection = Config::get("general_config.mongo.use_collection_detail_anime");
    }
}