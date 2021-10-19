<?php
namespace FullPay\PaymentAPI\models ;


/**
 *
 * @property string $value 代碼 - 對應paymentId
 * @property string $name 名稱 - 中文名稱
 * @property string $language 語言地區 - zh-CN
 * @property boolean $enabled 啟用
 */
class Platform
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __get ($key)
    {
        return $this->data[$key] ?? null;
    }

    public function __isset ($key)
    {
        return isset($this->data[$key]);
    }

}
