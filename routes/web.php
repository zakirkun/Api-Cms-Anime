<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->get('/key', function(){
    return str_random(32);
});
// AnimeIndo
$router->post('/ListAnime-A', 'AnimeIndo\ListAnimeController@ListAnime');
$router->post('/SingleListAnime-A', 'AnimeIndo\SingleListAnimeController@SingleListAnim');
$router->post('/StreamAnime-A', 'AnimeIndo\StreamAnimeControllerV2@StreamAnime');
$router->post('/SearchAnime-A', 'AnimeIndo\SearchAnimeControolerV2@SearchAnime');
$router->post('/GenreListAnime-A', 'GenreListAnimeController@GenreListAnime');
$router->post('/SearchGenreAnime-A', 'SearchGenreAnimeController@SearchGenreAnime');
$router->post('/TrandingWeekAnime-A', 'AnimeIndo\TrandingWeekAnimeController@TrandingWeekAnime');
$router->post('/LastUpdateAnime-A', 'AnimeIndo\LastUpdateEpsAnimController@LastUpdateAnime');
$router->post('/ScheduleAnime-A', 'ScheduleAnimeController@ScheduleAnime');

require_once "apiV1.php";
