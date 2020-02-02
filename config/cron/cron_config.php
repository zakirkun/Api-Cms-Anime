<?php

#MYSQK
$config['Cron_ListAnimeGenerate'] = env('CRON_LIST_ANIME_GENERATE_V1', FALSE);
$config['Cron_DetailListAnimeGenerateByAlfabet'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYALFABET_V1', FALSE);
$config['Cron_DetailListAnimeGenerateByDate'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYDATE_V1', FALSE);
$config['Cron_LastUpdate_Generate'] = env('CRON_LASTUPDATE_GENERATE_V1', FALSE);
$config['Cron_StreamAnime_GenerateByDate'] = env('CRON_STREAMANIME_GENERATEBYDATE_V1', FALSE);
$config['Cron_StreamAnime_GenerateByID'] = env('CRON_STREAMANIME_GENERATEBYID_V1', FALSE);
$config['Cron_Trendingweek_Generate'] = env('CRON_TRENDINGWEEK_GENERATE_V1', FALSE);
$config['Cron_GenreAnime_Generate'] = env('CRON_GENREANIME_GENERATE_V1', FALSE);
$config['Cron_ScheduleAnime_Generate'] = env('CRON_SCHEDULEANIME_GENERATE_V1', FALSE);

#MONGO
$config['Cron_DetailListAnimeGenerateByDateMG'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYDATE_MGV1', FALSE);
$config['Cron_DetailListAnimeGenerateByAlfabetMG'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYALFABET_MGV1', FALSE);
$config['Cron_GenreAnime_Generate_ByAlfabet'] = env('CRON_GENREANIME_GENERATE_ByALFABETV1', FALSE);
$config['Cron_ListAnimeGenerateByDate'] = env('CRON_LIST_ANIME_GENERATE_BYDATE_V1', FALSE);
$config['Cron_ListAnimeGenerateByAlfabet'] = env('CRON_LIST_ANIME_GENERATE_BYALFABET_V1', FALSE);
$config['Cron_LastUpdate_GenerateByDateMG'] = env('CRON_LASTUPDATE_GENERATEBYDATE_MGV1', FALSE);
$config['Cron_StreamAnime_GenerateByDateMG'] = env('CRON_STREAMANIME_GENERATEBYDATE_MGV1', FALSE);
$config['Cron_StreamAnime_GenerateByIDMG'] = env('CRON_STREAMANIME_GENERATEBYID_MGV1', FALSE);



return $config;