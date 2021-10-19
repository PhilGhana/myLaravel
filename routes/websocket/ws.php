<?php

// Route::post('')

Route::namespace('WebSocket')->group(function () {

    Route::any('agent/login', 'WSAgentController@agentLogin');


    Route::any('agent/review/all', 'WSAgentController@reviewAll');


    Route::any('agent/letter/num-messages', 'WSAgentController@numMessages');


    Route::any('member/login', 'WSAgentController@memberLogin');


    Route::any('provider/sync-report/time-range', 'WSProviderController@syncReport');

    # 取得需要拉報表的設定
    Route::any('provider/sync-report/configs', 'WSProviderController@configs');

});
