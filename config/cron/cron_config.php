<?php

$config['Cron_ListAnimeGenerate'] = env('CRON_LIST_ANIME_GENERATE_V1', FALSE);
$config['Cron_DetailListAnimeGenerateByAlfabet'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYALFABET_V1', FALSE);
$config['Cron_DetailListAnimeGenerateByDate'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYDATE_V1', FALSE);
$config['Cron_LastUpdate_Generate'] = env('CRON_LASTUPDATE_GENERATE_V1', FALSE);
$config['Cron_StreamAnime_GenerateByDate'] = env('CRON_STREAMANIME_GENERATEBYDATE_V1', FALSE);
$config['Cron_StreamAnime_GenerateByID'] = env('CRON_STREAMANIME_GENERATEBYID_V1', FALSE);
$config['Cron_Trendingweek_Generate'] = env('CRON_TRENDINGWEEK_GENERATE_V1', FALSE);
$config['Cron_GenreAnime_Generate'] = env('CRON_GENREANIME_GENERATE_V1', FALSE);
$config['Cron_ScheduleAnime_Generate'] = env('CRON_SCHEDULEANIME_GENERATE_V1', FALSE);



return $config;