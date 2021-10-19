<?php

use Illuminate\Support\Facades\Route;

Route::post('/add', 'AgentController@addAgent');

Route::post('/edit', 'AgentController@editAgent');

Route::post('/edit-password', 'AgentController@editPassword');

Route::post('/percent-config/save', 'AgentController@savePercentConfig');

Route::post('/toggle-enabled', 'AgentController@toggleEnabled');

Route::post('/toggle-locked', 'AgentController@toggleLocked');

Route::post('/reset/error-count', 'AgentController@resetErrorCount');

Route::get('/percent-config', 'AgentController@getPercentConfig');

Route::get('/find', 'AgentController@findByAccount');

Route::get('/list', 'AgentController@getAgentList');

Route::get('/invite-info', 'AgentController@getInviteInfo');

// 取得可設定的角色權限
Route::get('/roles', 'AgentController@getRoles');

// 取得子帳號的角色權限
Route::get('/roles/sub', 'AgentController@getSubRoles');

// 查詢子帳號資料
Route::get('/sub/find', 'AgentController@findSubByAccount');

// 查詢子帳號資料列表
Route::get('/sub/list', 'AgentController@getSubList');

Route::get('/wallet', 'AgentController@getWallet');

// 修改代理「可用餘額」
Route::post('/wallet/edit-money', 'AgentController@editWalletMoney');

// 修改代理「結算餘額」
Route::post('/wallet/edit-settlement', 'AgentController@editWalletSettlement');

// 派點給成員
Route::post('/wallet/give-agent', 'AgentController@walletGiveAgent');

// 從成員收回點數
Route::post('/wallet/take-back-agent', 'AgentController@walletTakeBackAgent');

// 補點給成員 (系統錯誤類型補點)
Route::post('/wallet/give-error', 'AgentController@walletGiveError');

// 收回點數 (系統錯誤類型操作)
Route::post('/wallet/take-back-error', 'AgentController@walletTakeBackError');

// 預借額度
Route::post('/wallet/loan', 'AgentController@walletLoan');

// 沖銷額度
// Route::post('/wallet/write-off', 'AgentController@walletWriteOff');

// 查看代理交易紀錄
Route::get('/wallet-log/list', 'AgentController@getWalletLogList');

// 切換白名單啟停用
Route::post('/ip-whitelist/toggle', 'AgentController@ipWhitelistToggle');

// 設定代理的IP白名單
Route::post('/ip-whitelist/add', 'AgentController@ipWhitelistAdd');

// 移除代理的IP白名單
Route::post('/ip-whitelist/remove', 'AgentController@ipWhitelistRemove');

// 查詢代理的IP白名單
Route::get('/ip-whitelist/all', 'AgentController@ipWhitelistAll');

// 查詢直屬上、下層代理
Route::get('/related-agents', 'AgentController@relatedAgents');

// QRCode
Route::get('/invitation-qrcode', 'AgentController@qrcode');

// 取得加盟主下一層的agent
Route::get('/franchisee-first-agent', 'AgentController@franchiseeFirstAgent');

// 轉移邀請碼
Route::post('/invitation-code/move', 'AgentController@moveInvitationCode');

// 查詢轉移邀請碼紀錄
Route::get('/invitation-code/move/log', 'AgentController@getMoveInviteLog');

// 取得推廣資料 (邀請碼、網址)
Route::get('/invite-info/fetch', 'AgentController@fetchInviteInfo');

Route::prefix('/deposit-announcement')->group(function () {
    Route::get('/list', 'DepositAnnouncementController@list');
    Route::post('/add', 'DepositAnnouncementController@add');
    Route::post('/edit', 'DepositAnnouncementController@modify');
    Route::post('/toggle-enabled', 'DepositAnnouncementController@modify');
    Route::get('/detail', 'DepositAnnouncementController@getDetail');
    Route::get('/agent', 'DepositAnnouncementController@getAgents');
});
