<?php

// Nanime
$router->group(['prefix' => 'N/1', 'namespace' => 'Nanime'], function () use ($router){
    
    // $router->get('testing', 'MainController@testing');

    $router->post('LastUpdateAnime', 'LastUpdateEpsAnimController@LastUpdateAnime');
    $router->post('ListAnime', 'ListAnimeController@ListAnime');
    $router->post('GenreListAnime', 'GenreListAnimeController@GenreListAnime');
    // $router->post('SearchAnime', 'SearchAnimeControoler@SearchAnime');
    $router->post('SingleListAnime', 'SingleListAnimeController@SingleListAnim');
    // $router->post('SearchGenreAnime', 'SearchGenreAnimeController@SearchGenreAnime');
    $router->post('TrandingWeekAnime', 'TrandingWeekAnimeController@TrandingWeekAnime');
    $router->post('StreamAnime', 'StreamAnimeController@StreamAnime');
    $router->post('ScheduleAnime', 'ScheduleAnimeController@ScheduleAnime');

});

