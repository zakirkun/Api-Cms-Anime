<?php

namespace App\Models\V1\Mongo;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

use Config;

class CollectionsModel extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'contents';
    protected $guarded = [];
    protected $primarykey = "_id";

    public function __construct() {
        $this->collection = Config::get('kanalone.mongo.use_collection'); #replace $collection from env
    }
}