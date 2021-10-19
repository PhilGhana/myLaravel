<?php

namespace GameProvider\Operator\Multi\Parser;

class WM extends ParserBase
{

    const KEY = 'wm';

    public function html(): string
    {
        $content = (array) $this->content;

        return view('report.live', [
            'game'    => self::trans(self::KEY, "gid.{$content['gid']}"),
            'content' => $this->getBetContent((string) $content['betCode']),
            'result'  => implode('<br>', [
                $this->getBetContent(strval($content['result'] ?? '')),
                '牌型: ' . $content['gameResult'],
            ]),
        ])
        ->render();
    }

    public function getBetContent(string $betCode)
    {
        if (!strlen($betCode)) {
            return '';
        }
        $trans = self::trans(self::KEY, "betCode.$betCode");

        if (!self::isTransStr($trans, $betCode)) {
            return $betCode;    // 太複雜的betCode先用原始的
        }
        return $trans;
    }

}
