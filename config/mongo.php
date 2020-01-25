<?php

$config = [
	'collections_list_anime' => env('DB_COLLECTIONS_LIST_ANIME', ''),
	'collections_last_update' => env('DB_COLLECTIONS_LAST_UPDATE', ''),
	'collections_detail_anime' => env('DB_COLLECTIONS_DETAIL_ANIME', ''),
	'collections_list_episode' => env('DB_COLLECTIONS_LIST_EPISODE', ''),
	'collections_stream_anime' => env('DB_COLLECTIONS_STREAM_ANIME', ''),

];

return $config;
