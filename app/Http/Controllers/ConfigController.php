<?php

namespace App\Http\Controllers;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use \App\Http\Controllers\Controller;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Client;
use \Carbon\Carbon;
use \Sunra\PhpSimple\HtmlDomParser;


class ConfigController 
{
    public $BASE_URL_LIST_ANIME_2;
    public $BASE_URL_ANIME_2;
    public $BASE_URL_LIST_ANIME_1;
    public $BASE_URL_ANIME_1;
    public function __construct(){
        $this->BASE_URL_LIST_ANIME_1="https://nanime.tv/index/anime/";
        $this->BASE_URL_ANIME_1="https://nanime.tv";
        $this->BASE_URL_LIST_ANIME_2="https://animeindo.to/anime-list/";
        $this->BASE_URL_ANIME_2="https://animeindo.to/";
    }
    
}