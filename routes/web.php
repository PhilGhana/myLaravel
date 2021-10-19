<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------- -----------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', 'PublicController@index');

// 前台設定檔
Route::get('/config', 'PublicController@assetConfig');

if (config('app.debug')) {
    Route::get('/routes', 'DevController@routes');
    Route::get('/walletTest/{key}/{func}', 'DevController@walletTest');
    Route::get('/menus', 'DevController@menus');
    Route::get('/bet/{memberId}', 'DevController@bet');
    Route::get('/deduct/{memberId}/{reportId}', 'DevController@deduct');
    Route::get('/config/api', 'DevController@printApiConfig');
}

Route::get('test', 'DevController@test');

