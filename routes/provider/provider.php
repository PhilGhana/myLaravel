<?php
use App\Http\Controllers\Partner\SeamlessController;

// Route::prefix('booongo')
//     ->namespace('Partner')
//     ->group(base_path('routes/partner/partner-booongo.php'));

# 部分遊戲商設定的路徑不正確，需將 partner 換成 provider
Route::prefix('partner')->group(function() {
    Route::any('{key}', 'Provider\SeamlessController@index');
});

Route::prefix('provider')->middleware(['api_time'])->group(function() {
    Route::any('{key}', 'Provider\SeamlessController@index');

    # Dragoon Soft 專用
    Route::any('DragoonSoft/api/wallet/{action}', 'Provider\SeamlessController@dragoonSoftIndex');

    # ifun 專用
    Route::any('ifun/{action}', 'Provider\SeamlessController@ifunIndex');

    # allbet 專用
    Route::any('allbet/{action}', 'Provider\SeamlessController@allbetIndex');
    Route::any('allbet/{action}/{user}', 'Provider\SeamlessController@allbetIndex');

    #STG專用
    Route::any('stg', 'Provider\SeamlessController@stgIndex');

    # DG 專用
    Route::any('DG//user/{action}/{user}', 'Provider\SeamlessController@dgIndex');
    Route::any('DG//account/{action}/{user}', 'Provider\SeamlessController@dgIndex');

    # ICONIC 驗證用
    Route::get('ICONIC/check', 'Provider\SeamlessController@ICONICCheckToken');
});
