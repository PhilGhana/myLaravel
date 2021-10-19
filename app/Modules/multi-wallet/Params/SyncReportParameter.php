<?php

namespace MultiWallet\Params;

class SyncReportParameters
{
    /**
     * 開始時間
     *
     * @var string
     */
    public $startAt = null;

    /**
     * 結束時間
     *
     * @var string
     */
    public $endAt = null;

    /**
     * 注單狀態
     *
     * @var string
     */
    public $status = null;

    /**
     * 產品錢包代碼 (IMOne API)
     * MWG 捕魚錢包 (MWG Fishing Wallet) : 2
     * GG 捕魚錢包 (GG Fishing Wallet) : 4
     * IM 老虎機錢包 (IM Slot Wallet) : 101
     * PlayTech 錢包 (PlayTech Wallet) : 102
     * IM 娛樂場錢包 (IM Live Dealer Wallet) : 201
     * IM 體育博彩錢包 (IM Sportsbook Wallet) : 301
     * IM 電子競技錢包 (IM eSports Wallet) : 401
     * IG 彩票錢包 (IG Lottery Wallet) : 502
     * VR 彩票錢包 (VR Lottery Wallet) : 503
     * SG WIN 彩票錢包 (SG WIN Lottery Wallet) : 504
     * 樂遊棋牌錢包 (Le You Gaming Wallet) : 602
     * 開源棋牌錢包 (Kai Yuan Gaming Wallet) : 603
     * VG 棋牌錢包 (VG Gaming Wallet) : 604
     * AS 棋牌錢包(AS Gaming Wallet) : 605
     * 美天棋牌錢包 (MT Gaming Wallet) : 606
     * SG WIN 棋牌錢包 (SG Win Gaming Wallet) : 607
     * Z88 棋牌錢包 (Z88 Gaming Wallet) : 608
     * Lucky 棋牌錢包 (Lucky Gaming Wallet) : 609
     * IM 棋牌錢包 (IM Gaming Wallet) : 610
     * IM 電玩城錢包 (IM Gamezone Wallet) : 702
     *
     * @var integer
     */
    public $productWallet = null;

    /**
     * 篩選時間
     * 此參數用於决定 StartDate 和 EndDate 的定義
     * 1 = 下注時間 (Bet Date 注单的下注時間)
     * 2 = 比赛時間 (Event Date 所投注比赛的開赛時間)
     *
     * @var integer
     */
    public $dateFilterType = null;

     /**
     * 使用者參數
     *
     * @var MemberParameter
     */
    public $member = null;
}
