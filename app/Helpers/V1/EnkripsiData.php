<?php

namespace App\Helpers\V1;

use Carbon\Carbon;
use Illuminate\Support\Str;

class EnkripsiData
{
    public static function DecodeKeylistAnime($KeyListAnim){
        $lengtChacarter = (strlen($KeyListAnim));
        $decode = str_replace('QRCAbuK', "=", $KeyListAnim);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10, $lengtChacarter);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('QWTyu', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public static function DecodeKeyListEps($KeyEpisode){
        $lengtChacarter = (strlen($KeyEpisode));
        $decode = str_replace('QRCAbuK', "=", $KeyEpisode);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10, $lengtChacarter);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('QtYWL', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }

    public static function encodeKeyListGenre($KeyListGenreEnc){
        $result = base64_encode(json_encode($KeyListGenreEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $lengtChacarter = (strlen($result));
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, $lengtChacarter);
        $KeyListGenre = $iduniq0 . "RqWtY" . $iduniq1;
        return $KeyListGenre;
    }

    public static function encodeKeyListAnime($KeyListAnimEnc){
        $result = base64_encode(json_encode($KeyListAnimEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $lengtChacarter = (strlen($result));
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, $lengtChacarter);
        $result = $iduniq0 . "QWTyu" . $iduniq1;
        $KeyListAnim = $result;
        return $KeyListAnim;
    }

    public static function encodeKeyEpisodeAnime($KeyEpisodeEnc){
        $result = base64_encode(json_encode($KeyEpisodeEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $lengtChacarter = (strlen($result));
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, $lengtChacarter);
        $result = $iduniq0 . "QtYWL" . $iduniq1;
        $KeyEpisode = $result;
        return $KeyEpisode;
    }

    public static function encodePaginationEps($ListEncript){
        $KeyPegiAnimEnc= array(
            "href"=>$ListEncript
        );
        $result = base64_encode(json_encode($KeyPegiAnimEnc));
        $result = str_replace("=", "QRCAbuK", $result);
        $lengtChacarter = (strlen($result));
        $iduniq0 = substr($result, 0, 10);
        $iduniq1 = substr($result, 10, $lengtChacarter);
        $result = $iduniq0 . "MTrU" . $iduniq1;
        $KeyEncript = $result;

        return $KeyEncript;
    }

    public static function DecodePaginationEps($KeyPagination){
        $lengtChacarter = (strlen($KeyPagination));
        $decode = str_replace('QRCAbuK', "=", $KeyPagination);
        $iduniq0 = substr($decode, 0, 10);
        $iduniq1 = substr($decode, 10, $lengtChacarter);
        $result = $iduniq0 . "" . $iduniq1;
        $decode2 = str_replace('MTrU', "", $result);
        $KeyListDecode= json_decode(base64_decode($decode2));
        return $KeyListDecode;
    }
}