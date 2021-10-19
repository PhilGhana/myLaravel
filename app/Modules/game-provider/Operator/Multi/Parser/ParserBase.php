<?php

namespace GameProvider\Operator\Multi\Parser;

abstract class ParserBase
{

    const BASE_LANG_PRIFIX = 'provider/';

    protected $content = null;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public static function trans($provider, $path, $replace = [])
    {
        return __(static::BASE_LANG_PRIFIX . $provider . '.' . $path, $replace);
    }

    public static function isTransStr(string $trans, string $str)
    {
        return !(substr_compare($trans, $str, -strlen($str)) === 0);
    }

    public static function toHtml($content)
    {
        try {
            return preg_replace('/\t/', '', (new static($content))->html());
        } catch (\Exception $e) {
            return '<div>error content: ' . $e->getMessage() . '</div>';
        }
    }

    abstract public function html(): string;
}
