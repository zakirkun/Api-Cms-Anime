<?php

namespace App\Helpers\V1;

use Carbon\Carbon;
use Illuminate\Support\Str;


class Converter
{
    
    static function __normalizeUrl($input) {
      $pattern = '@(http(s)?://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
		  return $output = preg_replace($pattern, '<a href="http$2://$3">Klik Disini</a>', $input);
	  }

    static function __clearUtf($text){
      return $string = iconv('UTF-8', 'UTF-8//IGNORE', $text); // or
    }
    
    static function __normalizeSummary($text){
        // Strip HTML Tags
        $clear = strip_tags($text);

        $clear = str_replace(["“","”","–"], ["","","-"], $clear);
        // Clean all special characters
        $clear = htmlentities($clear);
        // Clean up things like &amp;
        $clear = html_entity_decode($clear);
        // Strip out any url-encoded stuff
        $clear = urldecode($clear);
        // Replace Multiple spaces with single space
        $clear = preg_replace('/ +/', ' ', $clear);
        // Trim the string of leading/trailing space
        $clear = trim($clear);
        $clear = self::__normalizeUrl($clear);
        $clear = self::__clearUtf($clear);

        return $clear;
    }

    static function __normalizeTitle($Title,$href){
      
      if (stripos((self::__normalizeSummary($Title)),'[email') !== false 
      || stripos($Title,'&') || stripos($Title,';')) {
          $Title = substr($href, strrpos($href, '/' )+1);
          $Title = str_replace("-"," ",$Title);
      }else{
          $Title = $Title;
      }
      return $Title;
    }

    static function __normalizeNameEps($href){
        $hrefDetailAnime = $href;
        $SlugAnime = substr($hrefDetailAnime, strrpos($hrefDetailAnime, '/' )+1);
        $SlugAnime = str_replace("-00","-",$SlugAnime);
        $SlugAnime = str_replace("-0","-",$SlugAnime);
        $SlugAnime = str_replace("-"," ",$SlugAnime);
        return $SlugAnime;
    }

    static function __normalizeNameEpsChar($NameEps,$hrefEps){
      if (stripos((self::__normalizeSummary($NameEps)),'[email') !== false|| stripos($NameEps,'&') || stripos($NameEps,';')){
        $NameEps = self::__normalizeNameEps($hrefEps);
      }else{
        $NameEps = $NameEps;
      }
      return $NameEps; 
    }

    public static function __filterCodeEpisodeAnime($href){
        $hrefEpisode = $href;
        $SlugEpisode = self::__normalizeNameEps($href);
        $SlugEpisode = Str::slug($SlugEpisode);
        $TipeMovie = (strstr($hrefEpisode,'episode')) ? "episode" : "movie";
        $SlugListEp = ($TipeMovie == "movie") ? $SlugEpisode."-".$TipeMovie : $SlugEpisode;
        return $SlugListEp;
    }
}