<?php

namespace GameProvider\Operator\Params;

class SyncReportParameter
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
     * 遊戲編號
     *
     * @var string
     */
    public $gameId = null;
}
