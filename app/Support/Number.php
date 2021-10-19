<?php

namespace App\Support;

class Number
{


    /**
     * 是否為整數
     *
     * @param mixed $value 檢查值
     * @return boolean
     */
    public static function isInteger($value)
    {
        return $value < 0
            ? intval(floor($value)) === intval($value)
            : intval(ceil($value)) === intval($value);
    }

    /**
     * 是否為自然數 (大於 0 的正整數)
     *
     * @param mixed $value 檢查值
     * @return boolean
     */
    public static function isNatural($value)
    {
        return static::isInteger($value) && intval($value) > 0;
    }

    public static function round($value, $precision = 0)
    {
        $v = floatval($value) * pow(10, $precision);
        $v = floor($v + 0.5);
        return ($v / pow(10, $precision)) + 0.0;
    }
}
