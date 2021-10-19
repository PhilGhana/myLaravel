<?php


Route::post('/add', 'GameController@addGame');

Route::post('/edit', 'GameController@editGame');

Route::post('/toggle-enabled', 'GameController@toggleEnable');

Route::post('/toggle-maintain', 'GameController@toggleMaintain');

Route::get('/list', 'GameController@getGameList');

Route::get('game-type-options', 'GameController@gameTypeOptions');

Route::get('/game-options', 'GameController@getGameOptions');

Route::get('/platform-options', 'GameController@getGamePlatformOptions');

Route::get('/game-tag-options', 'GameController@getGameTagOptions');

Route::post('/game-batch-toggle-maintain', 'GameController@batchToggleMaintain');
