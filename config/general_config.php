<?php

$config['environtment'] = strtolower(env('APP_ENV', 'local'));
$config['enable_sentry'] = env('SENTRY_ENABLE',FALSE);

$config['mongo']['query_timeout'] = (int) env('DB_TIMEOUT', 500000);
$config['mongo']['use_collection'] = env('DB_COLLECTIONS', 'contents');
$config['mongo']['use_collection_list_anime'] = env('DB_COLLECTIONS_LIST_ANIME', 'contents');
$config['mongo']['use_collection_last_update'] = env('DB_COLLECTIONS_LAST_UPDATE', 'contents');
$config['mongo']['use_collection_detail_anime'] = env('DB_COLLECTIONS_DETAIL_ANIME', 'contents');
$config['mongo']['use_collection_list_episode'] = env('DB_COLLECTIONS_LIST_EPISODE', 'contents');
$config['mongo']['use_collection_stream_anime'] = env('DB_COLLECTIONS_STREAM_ANIME', 'contents');
$config['mongo']['use_collection_trending_week'] = env('DB_COLLECTIONS_TRENDING_WEEK', 'contents');
$config['mongo']['use_collection_genrelist_anime'] = env('DB_COLLECTIONS_GENRE_LIST_ANIME', 'contents');


return $config;
