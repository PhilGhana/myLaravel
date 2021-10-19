<?php

namespace GameProvider\Operator\Multi\Api;

use GameProvider\Operator\BaseApi;

use GameProvider\Operator\Multi\Config\AGConfigConstract;

use GameProvider\Operator\Multi\BaseMultiWalletInterface;

use GameProvider\Operator\Params\MemberParameter;
use GameProvider\Operator\Params\TransferParameter;
use GameProvider\Operator\Params\LaunchGameParameter;
use GameProvider\Operator\Params\SyncCallBackParameter;
use GameProvider\Operator\Params\SyncReportParameter;

use GameProvider\Exceptions\AesException;
use GameProvider\Exceptions\LoginException;
use GameProvider\Exceptions\GameListException;
use GameProvider\Exceptions\JSONException;
use GameProvider\Exceptions\BalanceException;
use GameProvider\Exceptions\CreateMemberException;
use GameProvider\Exceptions\LaunchGameException;
use GameProvider\Exceptions\SyncException;
use GameProvider\Exceptions\TransferException;

use GameProvider\Operator\Feedback\MemberFeedback;
use GameProvider\Operator\Feedback\TransferFeedback;
use GameProvider\Operator\Feedback\BalanceFeedback;
use GameProvider\Operator\Feedback\LaunchGameFeedback;

use GameProvider\Exceptions\CurlException;

use App\Models\Report;

class AG extends BaseApi implements BaseMultiWalletInterface
{
    protected $config;

    // 判斷存提款
    protected $mode;

    protected $token = null;

    protected $errorMessage = [
        'key_error'=>'Key值錯誤',
        'network_error'=>'網路問題導致資料遺失',
        'account_add_fail'=>'建新帳號失敗,密碼不正確或帳號已存在',
        'error'=>'其他錯誤',
        '1'=>'失敗,訂單未處理狀態',
        '2'=>'因無效的轉帳金額導致的失敗',
        'duplicate_transfer' => '重複轉帳',
        'not_enough_credit' => '餘額不足,未能轉帳'
    ];

    function __construct(array $config)
    {
        $this->config = new AGConfigConstract();
        $this->config->cagent = $config['agid'];
        $this->config->secret = $config['secret'];
        $this->config->apiUrl = $config['apiUrl'];
        $this->config->oddtype = $config['oddtype'];
        $this->config->domain = $config['domain'];
        $this->config->cur = $config['currency'];
        $this->config->language = $config['language'];

    }

    /**
     * 建立會員
     *
     * @param MemberParameter $member
     * @return MemberFeedback
     */
    public function createMember(MemberParameter $member)
    {
        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$member->username,
            'method=lg',
            'actype='.$member->actype,
            'password='.$member->password,
            'oddtype='.$this->config->oddtype, // 使用創建用戶的默認盤口,參數可以省略不帶
            'cur='.$this->config->cur,
        ];

        $memberFeedback = new MemberFeedback();
        $result = $this->doSendProcess($memberFeedback, $params);
        if($result->info == "0")
        {
            $memberFeedback->extendParam = $member->username;
            return $memberFeedback;
        }

        // 發生錯誤
        throw new CreateMemberException(get_class($this), 'create member error! error code : ' . $result->info, $this->errorMessage[$result->info]);
        // $memberFeedback->error_code = $result->info;
        // $memberFeedback->error_msg = $this->errorMessage[$result->info];

        // return $memberFeedback;
    }

    /**
     * 存款
     * 必須先查餘額，然後送出交易，再查餘額確認錢是不是真的進去了
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function deposit(TransferParameter $transfer)
    {
        $this->mode = 'IN';
        $prepare = $this->prepareTransfer($transfer);
        $flag = 0;

        if($prepare->info == "0")
        {
            $flag = 1;
        }else{
            $flag = 0;
        }

        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$transfer->member->username,
            'method=tcc',
            'billno='.$transfer->billno,
            'type='.$this->mode,
            'credit='.$transfer->amount,
            'actype='.$transfer->member->actype,
            'flag='.$flag,
            'password='.$transfer->member->password,
            'fixcredit='.$transfer->fixcredit,
            'gameCategory='.$transfer->gameCategory, //只有 AGTEX 才需要
            'cur='.$this->config->cur,
        ];

        $transferFeedback = new TransferFeedback();
        $result = $this->doSendProcess($transferFeedback, $params);

        if($result->info == "0")
        {
            $transferFeedback->response_code = $this->reponseCode;
            return $transferFeedback;

        }

        throw new TransferException(get_class($this), 'deposit error! error code : ' . $result->info, $this->errorMessage[$result->info]);
        // $transferFeedback->error_code = $result->info;
        // $transferFeedback->error_msg = $this->errorMessage[$result->info];

        // return $transferFeedback;
    }

    /**
     * 提款
     *
     * @param TransferParameter $transfer
     * @return TransferFeedback
     */
    public function withdraw(TransferParameter $transfer)
    {
        $this->mode = 'OUT';
        $prepare = $this->prepareTransfer($transfer);
        $flag = 0;
        // if($prepare instanceof TransferFeedback)
        // {
        //     $flag = 0;
        // }

        if($prepare->info == "0")
        {
            $flag = 1;
        }else{
            $flag = 0;
        }

        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$transfer->member->username,
            'method=tcc',
            'billno='.$transfer->billno,
            'type='.$this->mode,
            'credit='.$transfer->amount,
            'actype='.$transfer->member->actype,
            'flag='.$flag,
            'password='.$transfer->member->password,
            'fixcredit='.$transfer->fixcredit,
            'gameCategory='.$transfer->gameCategory, //只有 AGTEX 才需要
            'cur='.$this->config->cur,
        ];

        $transferFeedback = new TransferFeedback();
        $result = $this->doSendProcess($transferFeedback, $params);

        if($result->info == "0")
        {
            $transferFeedback->response_code = $this->reponseCode;
            return $transferFeedback;

        }

        throw new TransferException(get_class($this), 'withdraw error! error code : ' . $result->info, $this->errorMessage[$result->info]);
        // $transferFeedback->error_code = $result->info;
        // $transferFeedback->error_msg = $this->errorMessage[$result->info];

        // return $transferFeedback;
    }

    /**
     * 預備轉帳
     *
     */
    public function prepareTransfer($transfer)
    {
        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$transfer->member->username,
            'method=tc',
            'billno='.$transfer->billno,
            'type='.$this->mode,
            'credit='.$transfer->amount,
            'actype='.$transfer->member->actype,
            'password='.$transfer->member->password,
            'fixcredit='.$transfer->fixcredit,  //不可用額度, 只有 AGTEX 才需要
            'gameCategory='.$transfer->gameCategory, //只有 AGTEX 才需要
            'cur='.$this->config->cur
        ];

        $fullParams = $this->setParams($params);
        $response = $this->post($this->config->apiUrl, $fullParams, false);
        $result = $this->xml2js($response);

        return $result;
    }

    /**
     * 查詢訂單狀態
     * 存提款失敗調用
     */
    public function queryOrderStatus(TransferParameter $transfer)
    {
        $params = [
            'cagent='.$this->config->agid,
            'billno='.$transfer->billno,
            'method=qos',
            'actype='.$transfer->member->actype,
            'cur='.$this->config->cur,
        ];

        $fullParams = $this->setParams($params);
        $response = $this->post($this->config->apiUrl, $fullParams, false);
        $result = $this->xml2js($response);

        return $result;
    }

    /**
     * 會員登入（取得遊戲路徑）
     * 不會回傳結果
     *
     * @param LaunchGameParameter $launchGameParams
     * @return void
     */
    public function launchGame(LaunchGameParameter $launchGameParams)
    {
        $lang = $this->setLanguage($launchGameParams->member->language);
        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$launchGameParams->member->username,
            'password='.$launchGameParams->member->password,

            'dm='.$this->config->domain,
            'sid='.$launchGameParams->sid,
            'actype='.$launchGameParams->member->actype,
            'lang='.$lang,
            'gameType='.$launchGameParams->gameType,
            'oddtype='.$this->config->oddtype,
            'cur='.$this->config->cur,
            'mh5='.$launchGameParams->device, //添加此參數為移動網頁版 不添加則跳轉pc遊戲
            // 待確認
            'session_token='
        ];

        $fullParams = $this->setParams($params);
        $this->post($this->config->apiUrl, $fullParams, false);

    }

    /**
     * 取得會員餘額
     *
     * @param MemberParameter $member
     * @return BalanceFeedback
     */
    public function getBalance(MemberParameter $member)
    {
        $params = [
            'cagent='.$this->config->agid,
            'loginname='.$member->username,
            'method=gb',
            'actype='.$member->actype,
            'password='.$member->password,
            'cur='.$this->config->cur,
        ];

        $balanceFeedback = new BalanceFeedback();
        $result = $this->doSendProcess($balanceFeedback, $params);

        if($this->reponseCode == 200)
        {
            $balanceFeedback->response_code = $this->reponseCode;
            $balanceFeedback->balance = $result->info;

            return $balanceFeedback;
        }

        // 發生錯誤
        throw new BalanceException(get_class($this), 'get balance error! error code : ' . $result->info, $this->errorMessage[$result->info]);
        // $balanceFeedback->error_code = $result->info;
        // $balanceFeedback->error_msg = $this->errorMessage[$result->info];

        // return $balanceFeedback;
    }

    /**
     * des 加密
     */
    public function encrypt($data, $key)
    {
        return openssl_encrypt($data, 'des-ecb', $key);
    }

    public function setParams($params)
    {
        $fullParams = implode("/\\\\\\\\\\\\\\\\/", $params);
        $secret = $this->config->secret;
        $targetParams = $this->encrypt($fullParams, $secret);
        $key = md5($targetParams.'MD5_Encrypt_key');
        $request = [
            'params' => $targetParams,
            'key'    => $key
        ];

        return $request;
    }

    public function doSendProcess($feedback, $params)
    {

        $fullParams = $this->setParams($params);

        $response = $this->post($this->config->apiUrl, $fullParams, false);
        $result = $this->xml2js($response);
        if($result == null)
        {
            throw new JSONException(get_class($this), 'error on JSON decode !', $result);
        }

        return $result;
    }

    // 新增 user_agent
    protected function curl($content, $url, $isPost = true, $need_json = true, $need_array = false)
    {
        // $this->curlHeader[] = 'Content-Length: ' . strlen($content);
        // 設置 httpclient
        $user_agent = 'WEB_LIB_GI_'.$this->config->cagent;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->curlHeader);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($isPost === true) {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        $result = curl_exec($ch);

        $reponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       $aaa  ="
       <row info = '0' msg = ''/>
       ";

        return ($aaa);


        // 如果對方發生錯誤，直接報錯，不處理
        if ($reponseCode !== 200) {
            // TODO : 這邊要寫到log
            throw new CurlException(get_class($this), 'curl error : ' . $url, json_encode($content));
        }

        curl_close($ch);

        if ($need_json === true) {
            return json_decode($result, $need_array);
        }

        return $result;
    }

    // xml 解析
    public function xml2js($xmlnode)
    {
        $xml = simplexml_load_string($xmlnode);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        $json = json_encode(array_pop($xml));
        $json = json_decode($json);

        return $json;
    }

    public function setLanguage($curLang)
    {

        $langToNum = [
            "zh-cn"  => 1,
            "zh-tw"  => 2,
            "en-us"  => 3,
            "euc-jp" => 4,
            "ko"     => 5,
            "th"     => 6,
            "vi"     => 8,
            "id"     => 11,
            "khm"    => 9,
            "prt"    =>23
        ];

        $lang = [
            "zh-Hans" => "zh-cn",
            "zh-Hant" => "zh-tw",
            "en"      => "en-us",
            "ja"      => "euc-jp",
            "ko"      => "ko",
            "th"      => "th",
            "vi"      => "vi",
            "id"      => "id",
            "in"      => "id",
            "km"      => "khm",
            "pt"      => "prt",
        ];

        if(array_key_exists($curLang, $lang)){

            return $langToNum->$lang[$curLang];
        }

        return  $this->config->language;

    }

}
