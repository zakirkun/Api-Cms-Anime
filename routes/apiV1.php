<?php

// Nanime
$router->group(['prefix' => 'N/1', 'namespace' => 'Nanime'], function () use ($router){
    
    // $router->get('testing', 'MainController@testing');

    // Generate to Mysql
    $router->post('LastUpdateAnime', 'LastUpdateEpsAnimController@LastUpdateAnime');
    $router->post('ListAnime', 'ListAnimeController@ListAnime');
        $router->get('ListAnime', 'ListAnimeController@ListAnime');
    $router->post('GenreListAnime', 'GenreListAnimeController@GenreListAnime');
        $router->get('GenreListAnime', 'GenreListAnimeController@GenreListAnime');
    $router->post('DetailListAnim', 'DetailListAnimeController@DetailListAnim');
    $router->post('TrandingWeekAnime', 'TrandingWeekAnimeController@TrandingWeekAnime');
        $router->get('TrandingWeekAnime', 'TrandingWeekAnimeController@TrandingWeekAnime');
    $router->post('StreamAnime', 'StreamAnimeController@StreamAnime');
    $router->post('AdflyDownload', 'ConvertAdflyDownload@AdflyDownload');
    $router->post('DeleteAdflyDownload', 'ConvertAdflyDownload@DeleteAdflyDownload');

    #No With Database just cloud
    $router->post('DeleteAdflyDownloadByGroup', 'ConvertAdflyDownload@DeleteAdflyDownloadbyGroup');
    

    // generate to mongo
    $router->post('ListAnimeGenerate', 'ListAnimeController@ListAnimeGenerate');
    $router->post('DetailAnimeGenerate', 'DetailListAnimeController@generateDetailAnime');
    $router->post('LastUpdateAnimeGenerate', 'LastUpdateEpsAnimController@generateLastUpdateAnime');
    $router->post('GenreListAnimeGenerate', 'GenreListAnimeController@generateGenreListAnime');
    $router->post('StreamAnimeGenerate', 'StreamAnimeController@generateStreamAnime');

});

