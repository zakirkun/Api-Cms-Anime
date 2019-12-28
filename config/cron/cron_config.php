<?php

$config['Cron_DetailListAnimeGenerate'] = env('CRON_DETAIL_LIST_ANIME_GENERATE_BYALFABET_V1', FALSE);
$config['Cron_Trendingweek_Generate'] = env('CRON_TRENDINGWEEK_GENERATE_V1', FALSE);
$config['Cron_LastUpdate_Generate'] = env('CRON_LASTUPDATE_GENERATE_V1', FALSE);


return $config;