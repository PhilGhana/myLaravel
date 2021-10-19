<?php

namespace GameProvider\Operator\Multi\Parser;

class SG extends ParserBase
{

    const KEY = 'sg';

    public function html(): string
    {
        $content = (array) $this->content;

        return view('report.lotto_sg', [
            'cs'      => self::trans(self::KEY, 'cs.' . $content['cs']),
            'play'    => self::trans(self::KEY, 'play.' . $content['play']),
            'content' => $this->parseContent($content['type'] ?? '', $content['content']),
        ])
        ->render();
    }

    protected function parseContent(string $order, string $data)
    {
        switch ($order) {
            case 'P':
                $tmp = explode('&', $data);
                for ($i = 0; $i < count($tmp); $i++) {
                    $tmp[$i] = str_replace('~', '、', $tmp[$i]);
                }
                return implode('<br>', $tmp);
            case 'S': case 'C': case '5': case '6': case '7': case '8': case '9': case '10': case '11': case '12':
                return str_replace('&', '、', $data);
            case 'A':
                return str_replace('&', '、', $data) . ' 全車';
            case 'D':
                $tmp = explode('&', $data);
                for ($i = 0; $i < count($tmp); $i++) {
                    $tmp[$i] = str_replace('~', '、', $tmp[$i]);
                }
                $tmp[0] = '雙連碰:' . $tmp[0];
                return implode('<br>', $tmp);
                break;
            case 'T':
                $tmp = explode('&', $data);
                for ($i = 0; $i < count($tmp); $i++) {
                    $tmp[$i] = str_replace('~', '、', $tmp[$i]);
                }
                $tmp[0] = '三連碰:' . $tmp[0];
                return implode('<br>', $tmp);
            case 'F':
                $tmp = explode('&', $data);
                for ($i = 0; $i < count($tmp); $i++) {
                    $tmp[$i] = '雙連碰:' . str_replace('~', '、', $tmp[$i]);
                }
                return implode('<br>', $tmp);
            case 'M':
                break;
            case 'B':
                $tmp = explode('&', $data);
                return '特:' . (str_replace('~', '、', $tmp[0])) . ' 碰 ' . (str_replace('~', '、', $tmp[1]));
        }
        return $data;
    }

}
