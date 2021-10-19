<?php

namespace GameProvider\Operator\Params;

class MemberParameter
{
    /**
     * 資料庫的使用者id
     *
     * @var integer
     */
    public $member_id = 0;

    /**
     * 會員唯一識別
     *
     * @var string
     */
    public $playerId = null;

    /**
     * 使用者名稱
     *
     * @var string
     */
    public $username = null;

    /**
     * 密碼
     *
     * @var string
     */
    public $password = null;

    /**
     * 使用者ip
     *
     * @var string
     */
    public $ip = null;

    /**
     * 額度
     *
     * @var integer
     */
    public $initialCredit = null;

    /**
     * 指定玩家盤口
     *
     * @var string
     */
    public $balk = null;

    /**
     * 暱稱
     *
     * @var string
     */
    public $nickname = null;
    /**
     * 使用者信箱
     *
     * @var string
     */
    public $email = null;

    /**
     * 使用者生日, 格式:ISO-8601
     *
     */
    public $birthdate = null;

    /**
     * 使用者國籍代碼
     *
     * @var string
     */
    public $country = 'China';

    /**
     * 遊戲預設語言
     * ISO 639-1：en、ja、zh-Hans、zh-Hant
     *
     * @var string
     */
    public $language = null;

    /**
     * 遊戲代碼
     *
     * @var string
     */
    public $gameCode = null;

    /**
     * 真錢或者試玩帳號,1=真錢,0=試玩
     *
     * @var number
     */
    public $actype = null;

    /**
     * 用户所属子渠道 ID
     *
     * @var integer
     */
    public $subplatid = null;
}
