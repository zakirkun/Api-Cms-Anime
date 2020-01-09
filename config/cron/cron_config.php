<?php

$config['Cron_DetailListAnimeGenerateByAlfabet'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYALFABET_V1', FALSE);
$config['Cron_DetailListAnimeGenerateByDate'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYDATE_V1', FALSE);
$config['Cron_LastUpdate_Generate'] = env('CRON_LASTUPDATE_GENERATE_V1', FALSE);
$config['Cron_ListEpisodeAnime_GenerateByDate'] = env('CRON_LISTEPSODEANIME_GENERATEBYDATE_V1', FALSE);
$config['Cron_Trendingweek_Generate'] = env('CRON_TRENDINGWEEK_GENERATE_V1', FALSE);
$config['Cron_GenreAnime_Generate'] = env('CRON_GENREANIME_GENERATE_V1', FALSE);
$config['Cron_ScheduleAnime_Generate'] = env('CRON_SCHEDULEANIME_GENERATE_V1', FALSE);



return $config;