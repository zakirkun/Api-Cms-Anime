<?php

$config['environtment'] = strtolower(env('APP_ENV', 'local'));
$config['enable_sentry'] = env('SENTRY_ENABLE',FALSE);

$config['mongo']['query_timeout'] = (int) env('DB_TIMEOUT', 500000);
$config['mongo']['use_collection'] = env('DB_COLLECTIONS', 'contents');


return $config;
