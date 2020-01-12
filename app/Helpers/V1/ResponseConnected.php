<?php

namespace App\Helpers\V1;

use Carbon\Carbon;
use Illuminate\Support\Str;

class ResponseConnected
{
    public static function Success($NameEnd,$Save,$LogSave,$awal){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>$NameEnd,
                "Status"=> "Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> "Success Save Mysql",
                    "Speed" => self::SpeedResponse($awal),
                    "Code" => 200
                ),
                "LogBody"=> array(
                    "DataLog"=>$LogSave
                )
            )
        );
        return $API_TheMovie;
    }

    public static function InternalServerError($NameEnd,$Message,$awal){
        $API_TheMovie=array(
            "API_TheMovieRs"=>array(
                "Version"=> "N.1",
                "Timestamp"=> Carbon::now()->format(DATE_ATOM),
                "NameEnd"=>$NameEnd,
                "Status"=> "Not Complete",
                "Message"=>array(
                    "Type"=> "Info",
                    "ShortText"=> $Message,
                    "Speed" => self::SpeedResponse($awal),
                    "Code" => 500
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }
    public static function PageNotFound($NameEnd,$Message,$awal){
        $API_TheMovie = array(
            "API_TheMovieRs" => array(
                "Version" => "N.1",
                "Timestamp" => Carbon::now()->format(DATE_ATOM),
                "NameEnd" => $NameEnd,
                "Status" => "Not Complete",
                "Message" => array(
                    "Type" => "Info",
                    "ShortText" => $Message,
                    "Speed" => self::SpeedResponse($awal),
                    "Code" => 404
                ),
                "Body" => array()
            )
        );
        return $API_TheMovie;
    }
    public static function InvalidToken($NameEnd,$Message,$awal){
        $API_TheMovie = array(
            "API_TheMovieRs" => array(
                "Version" => "N.1",
                "Timestamp" => Carbon::now()->format(DATE_ATOM),
                "NameEnd" => $NameEnd,
                "Status" => "Not Complete",
                "Message" => array(
                    "Type" => "Info",
                    "ShortText" => $Message,
                    "Speed" => self::SpeedResponse($awal),
                    "Code" => 203
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }

    public static function InvalidKey($NameEnd,$Message,$awal){
        $API_TheMovie = array(
            "API_TheMovieRs" => array(
                "Version" => "N.1",
                "Timestamp" => Carbon::now()->format(DATE_ATOM),
                "NameEnd" => $NameEnd,
                "Status" => "Not Complete",
                "Message" =>array(
                    "Type" => "Info",
                    "ShortText" => "Invalid Key",
                    "Code" => 401
                ),
                "Body"=> array()
            )
        );
        return $API_TheMovie;
    }

    public static function InvalidKeyPagination($NameEnd,$Message,$awal){
        $API_TheMovie = array(
            "API_TheMovieRs" =>array(
                "Version" => "N.1",
                "Timestamp" => Carbon::now()->format(DATE_ATOM),
                "NameEnd" =>$NameEnd,
                "Status" => "Not Complete",
                "Message" => array(
                    "Type" => "Info",
                    "ShortText" => "Invalid Key Pagination",
                    "Code" => 401
                ),
                "Body"=> array(
                    "StreamAnime" => array()
                )
            )
        );
        return $API_TheMovie;
    }

    public static function SpeedResponse($awal){
        $akhir = microtime(true);
        $durasi = $akhir - $awal;
        $jam = (int)($durasi/60/60);
        $menit = (int)($durasi/60) - $jam*60;
        $detik = $durasi - $jam*60*60 - $menit*60;
        return $kecepatan = number_format((float)$detik, 2, '.', '');
    }

}